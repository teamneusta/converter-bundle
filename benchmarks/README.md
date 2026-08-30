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

Four independent `bin/bench-baseline` runs on the same machine (Docker, PHP 8.4, Xdebug disabled
via `--php-config`), each `revs=100-1000` x `iterations=5`:

| Scenario | Diff vs. pre-#102 across 4 runs | Verdict |
|---|---|---|
| `FlatConversionBench` (no context) | -0.3% .. +2.4% | noise |
| `NestedConversionBench` depth 1/3/5 (no context) | -6.7% .. +6.8% | noise |
| `ArrayNestedConversionBench` k=1/10/100 (no context) | -1.5% .. +10.6% | noise |
| `LegacyContextMappingBench` (`GenericContext`) | **+5.4% .. +22.0%, always positive** | real, small |
| `CustomContextObjectBench` (arbitrary `$ctx`) | **+16.0% .. +26.2%, always positive** | real |

**The original concern - that nested/array structures cause exponentially more context creation
and merging - does not hold.** `ConvertingPopulator`/`ArrayConvertingPopulator` are byte-identical
before and after #102 and pass `$ctx` through unchanged; a `Context` is only ever built inside
`ConverterWithDefaultContext`, which is only wired up for converters that actually configure
`context_configurators`. The three context-free benchmarks above confirm this empirically: their
diffs are noise-level and don't grow with nesting depth or array size.

Two real, reproducible regressions were found instead, both on paths unrelated to
nesting/exponential growth:

1. **`GenericConverter::convert()`** now calls `trigger_deprecation()` on every call where `$ctx`
   is neither `Context` nor `GenericContext` (`CustomContextObjectBench`, ~+20% consistently).
   Affects any caller still passing a custom, non-`GenericContext` object as `$ctx`.
2. **`ContextMappingPopulator::populate()`** now allocates an extra `$readValue` closure per call
   even on the legacy `GenericContext` path (`LegacyContextMappingBench`, ~+13% on average, always
   positive but noisier/smaller than (1)).

Both are per-call, constant-factor costs on paths that already existed pre-#102 - not something
that compounds with nesting depth or array cardinality. `Current/` benchmarks show the same:
`DefaultContextNestedBench` scales linearly in both depth and array size (matches the `K x (M+1)`
clone-count model), never exponentially.

`SeedingBenefitBench` (defensive vs. unconditional configurator under #111's seeding) measured a
difference within its own noise band (rstdev) in this run - too small to draw a wall-clock
conclusion from. The real evidence for #111's benefit is the deterministic
`ContextAllocationCountTest::test_defensive_configurator_under_seeding_reuses_the_seeded_instance`,
which proves the defensive configurator does zero extra work (rather than just doing it faster).

Re-run `bin/bench-baseline` and update this table if `src/Context*` or the populators above change.
