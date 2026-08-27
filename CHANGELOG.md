# Changelog

## [Unreleased]

### Added

- New immutable `Neusta\ConverterBundle\Context` class, replacing `GenericContext` as the object passed as
  `$ctx` to `Converter`, `Populator` and `TargetFactory`. Unlike `GenericContext`, which stores arbitrary
  values under string keys, `Context` stores one instance per class (`Context::create(new MyContext(...))`)
  and is immutable (`with()`/`without()` return a new instance). Context objects are read with `get()`, which
  throws if the requested class is not present, or with `tryGet()`, which returns `null` instead and allows
  falling back to a default: `$ctx->tryGet(MyContext::class)->value ?? $default`. `has()` checks for presence.
- `Neusta\ConverterBundle\Context\ContextConfigurator` interface to build up a default context from services.
  Implement `configureContext(Context $ctx): Context` and register the service id via the new
  `context_configurators` option (see below).
- New bundle configuration options:
  - `neusta_converter.context_configurators` — service ids of `ContextConfigurator`s applied to every converter.
  - `neusta_converter.converter.<name>.context_configurators` — service ids of `ContextConfigurator`s applied
    only to that converter, in addition to the global ones.

  A converter that declares `context_configurators` (globally or locally) automatically receives a default
  `Context`, built from those configurators, as `$ctx` — even if the caller doesn't pass one. If the caller
  does pass a `Context`, it is merged on top of the default one, taking precedence per class.
- `neusta_converter.converter.<name>.context.<target>.class` — for `context:` property mappings, tells
  `ContextMappingPopulator` which object inside the new `Context` to read `property` from. Required as soon as
  the converter uses `context_configurators`; validated at container-compile time.
- `property` may be omitted for a `context:` mapping that declares `class`: if that class has exactly
  one (non-static) property, its name is used automatically; otherwise the target property name is used, as
  before. `class` itself can also be written as a plain class-string value instead of the nested form —
  `context: { locale: App\Context\LocaleContext }` is a shortcut for
  `context: { locale: { class: App\Context\LocaleContext } }`. A string value is only treated as this
  shortcut when it names an existing class; anything else is still treated as `property`, so the pre-`class`
  short form (`context: { locale: language }`) keeps working unchanged.

### Deprecated

- `Neusta\ConverterBundle\Converter\Context\GenericContext` is deprecated in favor of `Context`. It continues
  to work as before and remains fully supported until the next major version for converters that don't use
  `context_configurators`. Converters that *do* declare `context_configurators` (globally or locally) require a
  `Context` instance — passing a `GenericContext` (or any other non-`Context` object) to them throws an
  `InvalidArgumentException` immediately, not just a deprecation notice.
- More generally, passing any `$ctx` to `GenericConverter::convert()` that is neither `Context` nor (still
  supported) `GenericContext` is deprecated too — not just `GenericContext` itself. Everything still works
  unchanged; the notice is only a heads-up that arbitrary custom context types won't be accepted anymore in
  the next major version.

See [UPGRADE.md](UPGRADE.md) for migration instructions and what's planned for the next major version.
