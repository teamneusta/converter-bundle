# Performance benchmarks

A [phpbench](https://phpbench.readthedocs.io/) suite covering the conversion hot paths, written to
answer one question: did `Context` (#102, "New concept for the context", and its follow-ups #109,
#110, #111) make conversions slower - especially in nested/array structures, where the concern was
that contexts get rebuilt and merged repeatedly?

Hand-wired (no DI container, see `bootstrap.php`), so it runs unmodified against a checkout from
before #102.

## Layout

- `Comparable/` - scenarios that exist on both sides of #102 (flat/nested/array conversion without
  context, the legacy `GenericContext` path, an arbitrary `$ctx` object). Runs unmodified against
  an older commit via `bin/bench-baseline`.
- `Current/` - scenarios that only exist post-#102 (`Context` + `ContextMappingPopulator`,
  `ConverterWithDefaultContext`, the #111 seeding benefit). No baseline possible.
- `Fixtures/` - minimal source/target/context classes, kept separate from `tests/Fixtures/` so this
  suite stays decoupled from test-fixture drift and can run against an old checkout as-is.

## Running

```shell
bin/composer bench                 # run the full suite against this checkout
bin/composer bench -- benchmarks/Comparable/FlatConversionBench.php   # a single class
bin/bench-baseline                 # Comparable/ vs. a018c0a (the commit right before #102)
bin/bench-baseline <some-other-ref>
```

`bin/bench-baseline` runs `benchmarks/Comparable/` in a temporary git worktree at the given
commit, then again on the current checkout, and prints a diff (mode time and rstdev) via
phpbench's built-in baseline comparison (`--dump-file`/`--file`).

For a noise-free complement, see `tests/Converter/ContextAllocationCountTest.php`: it asserts the
exact number of `ContextConfigurator` invocations for given nesting shapes instead of timing them,
and runs in the normal PHPUnit suite/CI.

## Findings (as of #111, compared against a018c0a)

**The original concern - that nested/array structures cause exponentially more context creation
and merging - does not hold.** `ConvertingPopulator`/`ArrayConvertingPopulator` are byte-identical
before and after #102 and pass `$ctx` through unchanged; a `Context` is only ever built inside
`ConverterWithDefaultContext`, which is only wired up for converters that actually configure
`context_configurators`. `FlatConversionBench`/`NestedConversionBench`/`ArrayNestedConversionBench`
(all context-free) confirm this empirically: their diffs are noise-level and don't grow with
nesting depth or array size. `Current/` benchmarks show the same for the actual `Context` path:
`DefaultContextNestedBench` scales linearly in both depth and array size (matches the
`K x (M+1)` clone-count model), never exponentially.

Two small, constant-factor regressions were found on paths unrelated to nesting - both fixed
in this same change:

1. **`GenericConverter::convert()`** called `trigger_deprecation()` on *every* call where `$ctx`
   is neither `Context` nor `GenericContext` (`CustomContextObjectBench`, consistently ~+16..+26%
   across four runs before the fix). Fixed by warning only once per `(converter instance, $ctx
   class)` pair - the notice still reaches the user, it just doesn't re-pay for itself on every
   call of a long-lived (e.g. DI-shared) converter. Locked in by
   `GenericConverterTest::testConvertTriggersDeprecationOnlyOncePerCtxClass` /
   `::testConvertTriggersDeprecationAgainForADifferentCtxClass`.
2. **`ContextMappingPopulator::populate()`** allocated an extra `$readValue` closure per call, even
   on the legacy `GenericContext` path, purely to defer a call that happened unconditionally one
   line later (`LegacyContextMappingBench`, ~+5..+22% across four runs before the fix). Fixed by
   computing the value inline instead of wrapping it in a closure; exception-wrapping behavior for
   read failures is unchanged (see `test_populate_exceptional_case_wraps_read_failures_from_new_context`).

Three `bin/bench-baseline` runs after both fixes put both scenarios back inside the same noise
band as the context-free benchmarks (`LegacyContextMappingBench` +2.7%/+5.3%/+5.4%,
`CustomContextObjectBench` -0.7%/+3.2%/+3.2%, vs. e.g. `FlatConversionBench` -2.1%/-0.3%/+2.4%) -
no longer distinguishable from measurement noise. One run in between showed every subject jump
30-100%, context-free ones included, and was discarded as system-level noise (competing load on
the machine, not a code effect) - a useful reminder to sanity-check a `bin/bench-baseline`
diff against its own context-free rows before trusting a single run.

`SeedingBenefitBench` (defensive vs. unconditional configurator under #111's seeding) measured a
difference within its own noise band (rstdev) in this run - too small to draw a wall-clock
conclusion from. The real evidence for #111's benefit is the deterministic
`ContextAllocationCountTest::test_defensive_configurator_under_seeding_reuses_the_seeded_instance`,
which proves the defensive configurator does zero extra work (rather than just doing it faster).

Re-run `bin/bench-baseline` and update this table if `src/Context*` or the populators above change.
