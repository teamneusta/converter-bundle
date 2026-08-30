# Performance benchmarks

A [phpbench](https://phpbench.readthedocs.io/) suite covering the conversion hot paths, in
particular the `Context` system (#102 and its follow-ups) - a place where a regression is easy to
introduce and, without measurements, easy not to notice.

Hand-wired (no DI container, see `bootstrap.php`), so it runs unmodified against an older
checkout, e.g. one from before #102.

## Layout

- `Comparable/` - scenarios that exist both before and after #102 (flat/nested/array conversion
  without context, the legacy `GenericContext` path, an arbitrary `$ctx` object). Runs unmodified
  against an older commit via `bin/bench-baseline`.
- `Current/` - scenarios that only exist post-#102 (`Context` + `ContextMappingPopulator`,
  `ConverterWithDefaultContext`, the #111 seeding benefit). No baseline possible.
- `Fixtures/` - minimal source/target/context classes, kept separate from `tests/Fixtures/` so this
  suite stays decoupled from test-fixture drift and can run against an old checkout as-is.
- `Support/` - reusable helpers for writing benchmarks, e.g. `CyclesThroughPool` for subjects that
  need a distinct object per revolution instead of reusing the same one throughout.

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
