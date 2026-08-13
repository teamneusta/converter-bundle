# Upgrade Guide

## v2.0 (breaking)

The following changes are planned for the next major version and will require action beforehand:

- `GenericContext` will be removed.
- `Converter`, `Populator` and `TargetFactory` will always receive a `Context` instance as `$ctx` — for every
  converter, not just those with `context_configurators` configured. `$ctx` will no longer be optional or of
  an arbitrary custom type.

Converters that already use `context_configurators` today are already compatible with this future behavior —
migrating early, converter by converter, is possible now and recommended.

### Migrating from `GenericContext` to `Context`

`Context` stores one instance per class, so each piece of context data needs its own small class instead of a
string key:

```php
namespace App\Context;

final class LocaleContext
{
    public function __construct(
        public readonly string $locale,
    ) {
    }
}
```

```php
// before
$ctx = (new GenericContext())->setValue('locale', 'de');

// after
$ctx = Context::create(new LocaleContext('de'));
```

Populators reading from the context need the `objectType` of the object they read from:

```yaml
# before
context:
  locale: ~

# after
context:
  locale:
    objectType: App\Context\LocaleContext
    property: locale
```

### Migrating a custom (non-`GenericContext`) `$ctx`

If you pass your own context object directly to `convert()` today, wrap it instead:

```php
// before
$converter->convert($source, new MyContext(/* ... */));

// after
$converter->convert($source, Context::create(new MyContext(/* ... */)));
```
