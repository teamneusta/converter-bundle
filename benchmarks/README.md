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
- `Support/` - reusable helpers for writing benchmarks (see "Prepared for future work" below).

## Running

```shell
bin/composer bench                 # run the full suite against this checkout
bin/composer bench -- benchmarks/Comparable/FlatConversionBench.php   # a single class
bin/bench-baseline                 # Comparable/ vs. a018c0a (the commit right before #102)
bin/bench-baseline <some-other-ref>
```

`bin/bench-baseline` runs `benchmarks/Comparable/` in a temporary git worktree at the given
commit, then again on the current checkout, and prints a diff (mode time and rstdev) via
phpbench's built-in baseline comparison (`--dump-file`/`--file`). A `bin/bench-baseline` diff is
noisy on a shared/loaded machine - sanity-check it against its own context-free rows (they should
sit near 0%) before trusting a result on a context-touching one.

For a noise-free complement, see `tests/Converter/ContextAllocationCountTest.php`: it asserts the
exact number of `ContextConfigurator` invocations for given nesting shapes instead of timing them,
and runs in the normal PHPUnit suite/CI.

## Findings (current status)

| Scenario | vs. pre-#102 (`a018c0a`) |
|---|---|
| Flat / nested / array conversion, no context | noise-level, regardless of depth or array size |
| `ConverterWithDefaultContext`, depth x cardinality (`Current/`) | scales linearly in both - never exponentially |
| `GenericContext` + `ContextMappingPopulator` (legacy path) | noise-level (was ~+5..+22%, fixed - see below) |
| Arbitrary, deprecated `$ctx` object | noise-level (was ~+16..+26%, fixed - see below) |

**The original concern - that nested/array structures cause exponentially more context creation
and merging - does not hold.** A `Context` is only ever built inside `ConverterWithDefaultContext`,
which is only wired up where `context_configurators` are actually configured; nesting depth and
array cardinality scale it linearly (matches the `K x (M+1)` clone-count model). See
`ContextAllocationCountTest` for the deterministic proof of the same claim.

Two small, constant-factor regressions were found and fixed:

1. `GenericConverter::convert()` called `trigger_deprecation()` on every call with a non-`Context`/
   `GenericContext` `$ctx`. Fixed to warn once per `(converter instance, $ctx class)` instead -
   see `GenericConverterTest::testConvertTriggersDeprecationOnlyOncePerCtxClass`.
2. `ContextMappingPopulator::populate()` allocated a closure per call to defer a read that happened
   unconditionally right after. Fixed to compute the value inline - exception-wrapping for read
   failures is unchanged (`test_populate_exceptional_case_wraps_read_failures_from_new_context`).

`SeedingBenefitBench`'s wall-clock delta sits within its own noise band; the real evidence for
#111's seeding benefit is the deterministic
`ContextAllocationCountTest::test_defensive_configurator_under_seeding_reuses_the_seeded_instance`.

Re-run `bin/bench-baseline` if `src/Context*` or the populators above change again.

## Prepared for future work

- **#35 (`CachingConverter`)**: every benchmark here reuses the same source object across all
  revs, which would make a cache-aware converter measure cache hits only, not the realistic
  "every conversion is new" case. `Support/CyclesThroughPool.php` is a trait for cycling through a
  pre-built pool of distinct objects across revs instead (phpbench runs `revs` calls against the
  same instance in a tight loop, so a mutable counter works). `FlatConversionUniqueSourcesBench`
  uses it to confirm this suite's usual reused-source pattern doesn't itself skew results, and is
  the pattern a future cache-miss benchmark would build on. `CacheAwareContext::getHash()` (the
  cache-key contract on that branch) isn't implemented by `Context` yet - that's a design question
  for #35 independent of this suite.
- **#105 (custom populator contracts)**: needs no harness changes once merged.
  `CustomContractPopulator` is a plain `Populator` built from a closure + a `ParameterOrder` enum,
  matching this suite's existing hand-wired pattern - it doesn't touch the context system. Add its
  benchmarks under `Current/` (no baseline exists, like the `Context` classes here).
