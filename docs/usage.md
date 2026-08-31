## Usage

After the bundle is activated, you can directly use it by implementing populators for your target and source types.

Imagine your source type is `User`:

```php
class User
{
    private int $uuid;
    private string $firstname;
    private string $lastname;
    private string $email;
    private string $phone;
    
    // with getters and setters
}
```

and your target type is `Person`:

```php
class Person
{
    private string $fullName;
    private string $email;
    private string $phoneNumber;

    // with getters and setters
}
```

and your task is to transform a given `User` instance into a `Person` instance.
Of course, you can do it by instantiating a new `Person` and calling associated getters and setters in your code.
But - you shouldn't...why?

There are a lot of reasons, but at least the most important is:

Separation of Concerns.

You should use the Converter-and-Populator-pattern. But how?!

### Populators

Implement one or several populators:

```php
use Neusta\ConverterBundle\Populator;

/**
 * @implements Populator<User, Person>
 */
class PersonNamePopulator implements Populator
{
    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        $separator = ' ';
        $target->setFullName($source->getFirstname() . $separator . $source->getLastname());
    }
}
```

As you can see, implementation here is quite simple - just concatenation of two attributes.
But however transformation will become more and more complex, it should be done in a testable,
separated Populator or in several of them.

Skip thinking about the converter context at the moment. It will help you...
maybe not now but in a few weeks. You will see.

### Configuration

First register the populator as a service:

```yaml
# config/services.yaml
services:
  YourNamespace\PersonNamePopulator: ~
```

Then declare the following converter in your package config:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  converters:
    person.converter:
      generic:
        target:
          class: YourNamespace\Person
          # optionally pre-initialized values for target properties
          properties:
            email: 'mail@me.com'
        populators:
          - YourNamespace\PersonNamePopulator
          # additional populators may follow
```

The key below the converter id (`generic` here) is the **converter type**. It selects which kind of
converter is built and which options are available below it. `generic` is the built-in type backed by
`GenericConverter`; other bundles or your app can add their own types, see
[Adding your own converter and populator types](#adding-your-own-converter-and-populator-types).

> [!IMPORTANT]
> The old `neusta_converter.converter` and `neusta_converter.populator` keys (without the type level)
> are deprecated since 1.11 and will be removed in 2.0. To migrate, rename the key to its plural form
> and move the existing options one level down, below the type:
>
> ```diff
>  neusta_converter:
> -  converter:
> +  converters:
>     person.converter:
> -      target_factory: YourNamespace\PersonFactory
> -      populators:
> -        - YourNamespace\PersonNamePopulator
> +      generic:
> +        target_factory: YourNamespace\PersonFactory
> +        populators:
> +          - YourNamespace\PersonNamePopulator
> ```
>
> The deprecated `converter` keyword (the FQCN of a custom `Converter` implementation) has no direct
> replacement: a converter class with a different constructor is now expressed as its own converter
> *type*, see [Adding your own converter and populator types](#adding-your-own-converter-and-populator-types).

> [!TIP]
> You can use a custom implementation of the `TargetTypeFactory` interface via the `target_factory` keyword
> if you have special requirements when creating the target object.

> [!TIP]
> You can simply write `target: YourNameSpace\Person` instead of 
> ```
> target: 
>     class: YourNameSpace\Person
> ```
> and it will be transformed before normalization but in that case you can not define initialization properties.

#### Mapping properties

If you just want to map a single property from the source to the target without transforming it in between, you don't
need to write a custom populator for this, as this bundle already contains the `PropertyMappingPopulator` for this use
case.

You can use it in your converter config via the `properties` keyword:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  converters:
    person.converter:
      generic:
        target:
          class: YourNamespace\Person
        properties:
          email: ~
          phoneNumber: phone
```

Which will populate

`email` (property of the target object)

with `email` (property of the source object)

and

`phoneNumber` (property of the target object)

with `phone` (property of the source object).

> [!IMPORTANT]
> The source and the target property must be of the same type for this to work.

To set a default value for a property, you can use the `default` keyword:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  converters:
    person.converter:
      generic:
        target:
          class: YourNamespace\Person
        properties:
          phoneNumber:
            source: phone
            default: '0123456789'
```

The converter will set the value of `phoneNumber` (property of the target object) to `0123456789` if
the value of `phone` (property of the source object) is `null`.

#### Mapping context

If you just want to map a single property from the context to the target without transforming it in between, you don't
need to write a custom populator for this, as this bundle already contains the `ContextMappingPopulator` for this use
case.

Context data lives inside typed objects stored in a `Context` (see [Context](#context) below), so you need to tell
the populator which object to read the property from via `class`. Use it in your converter config via the
`context` keyword:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  converters:
    person.converter:
      generic:
        target:
          class: YourNamespace\Person
        context:
          group: YourNamespace\Context\GroupContext
          locale: YourNamespace\Context\LanguageContext
```

A plain class-string value like above is a shortcut for `class: YourNamespace\Context\GroupContext`. Which
property is read off that object is resolved in this order:

1. an explicit `property` key, if you give one,
2. the object's only property, if it has exactly one,
3. otherwise the target property name itself.

So, assuming `GroupContext` has a single property `name` and `LanguageContext` has a single property `language`,
the example above will populate

`group` (property of the target object)

with `name` (the only property of the `GroupContext` object)

and

`locale` (property of the target object)

with `language` (the only property of the `LanguageContext` object).

For a context object with more than one property (or to just rename explicitly), use the long form with `property`:

```yaml
context:
  locale:
    class: YourNamespace\Context\LanguageContext
    property: language
```

The `required` option (see below) is also only available in this long form — the class-string shortcut can't carry
extra options.

> [!IMPORTANT]
> The context and the target property must be of the same type for this to work.

> [!IMPORTANT]
> By default, if the relevant object (`GroupContext`/`LanguageContext` above) is not present in the `Context`
> passed to `convert()` — or if no context was passed at all — the populator silently does nothing and leaves the
> target property untouched: no default is applied and no exception is thrown. Set `required: true` to fail
> loudly instead:
>
> ```yaml
> context:
>   group:
>     class: YourNamespace\Context\GroupContext
>     required: true
> ```
>
> With `required: true`, a missing `GroupContext` (or a missing context altogether) throws a `PopulationException`
> at conversion time. This is different from a `property` that doesn't exist on the object at all (e.g. a typo):
> that is never checked when the container is compiled — regardless of `required` — and always throws a
> `PopulationException`, naming both properties, the first time `convert()` is called.

> [!NOTE]
> `class` is required as soon as the converter uses `context_configurators` (see [Context](#context)) — this is
> validated when the container is compiled. If the converter has no `context_configurators` and you still pass the
> deprecated `GenericContext` (see [UPGRADE.md](../UPGRADE.md)), you may omit `class` and use the short form
> `context: { group: ~, locale: language }`, exactly as before — a string value there is only treated as a `class`
> shortcut when it contains a namespace separator (`\`); a plain, unqualified value like `language` is always
> treated as `property`, so it can never be misread as an unrelated, unnamespaced class that happens to be loaded
> (PHP class name lookups are case-insensitive — e.g. ext-intl declares a global `Locale` class).

Under the hood, `context:` is the `context_mapping` populator type. Like every populator type, it is also usable
on its own under `populators:` — for example to reuse the same context mapping across several converters:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  populators:
    person.locale.populator:
      context_mapping:
        target: locale
        class: YourNamespace\Context\LanguageContext

  converters:
    person.converter:
      generic:
        target: YourNamespace\Person
        populators:
          - person.locale.populator
```

> [!NOTE]
> `context_mapping` does not implement `PropertyPopulatorFactory`, so it can't be nested inside `properties:`
> the way `converting`/`array_converting` can.

### Conversion

And now if you want to convert `User`s into `Person`s just type in your code:

```php
/** @var Converter<User, Person> */
$converter = $this->getContainer()->get('person.converter');
...
$person = $this->converter->convert($user);
```

Conversion done.

## Special Populators

After working a while with the converter pattern, you will notice that many scenarios in the population are very similar.
If the source property can be copied directly to the target property, but only the names of the properties change,
the same populator could be reused over and over again.

### Converting Populator

Let's go on with the following extended model classes:

```php
class Address
{
    private string $street;
    private string $number;
    private string $postalCode;
    private string $city;
}

class User
{
    private Address $address;
}
```

and the target type is `Person`:

```php
class PersonAddress
{
    private string $streetWithNumber;
    private string $postalCodeAndCity;
}

class Person
{
    private PersonAddress $address;
}
```

If you have a situation as above and your `User` will have an `Address` which should be populated into `Person`,
then you have to write a Populator which

* gets the `Address` from `User`,
* converts it into a `PersonAddress` object
* and sets it in `Person`.

The second step is typically a task for a (e.g. `Address`) converter.

Therefore, we have a `ConvertingPopulator` which can be used as follows:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  converters:
    person.converter:
      generic:
        # ...
        properties:
          address:
            converting:
              converter: address.converter

    address.converter:
      generic:
        # ...
```

The `converting` key below a property is the **populator type**. Without such a key the property is
mapped as-is (the default `property_mapping` type).

If you need the populator as a standalone service - for example to reuse it in several converters -
declare it under `populators` instead and reference it by its id:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  populators:
    person.address.populator:
      converting:
        target: address
        source: address
        converter: address.converter

  converters:
    person.converter:
      generic:
        # ...
        populators:
          - person.address.populator
```

Be aware - that both properties have the same name should not lead you think they have the same type.
There is really an object conversion behind done by `address.converter`.

If you specify the `sourcePropertyName` as an empty string, the full `source` object is used for the population.

Especially in connection with the `ConvertingPopulator` this is sometimes necessary.

#### Special case

In very rare situations it could happen that you want to use the complete source object for population of a special
attribute/property of your target object. In these case you can not define a source property name for the accessor but
you can use `'$this'` and the `ConvertingPopulator` (internally the `PropertyMappingPopulator` will use the object
`$source` itself as value.)

### ArrayConvertingPopulator

If you think that there is no 1:1 relation between `User` and `Address` (or corresponding Person and PersonAddress)
but a 1:n relation then the `ConvertingPopulator` cannot be used.

In these cases we have implemented an extended version of it called `ArrayConvertingPopulator`.

This populator uses the same internal technique but expects to convert an item of a source array of properties before
it will be set into the target object.

#### Example: User to Person

So imagine the addresses will now be an array of addresses (billing address, shipping addresses, contact
addresses, ...).

```php
class Address
{
    private string $street;
    private string $number;
    private string $postalCode;
    private string $city;
}

class User
{
    /** @var Address[] */
    private array $addresses;    
}
```

and the target type is `Person`:

```php
class PersonAddress
{
    private string $streetWithNumber;
    private string $postalCodeAndCity;
}

class Person
{
    /** @var PersonAddress[] */
    private array $addresses;
}
```

Now you have to declare the following populator:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  converters:
    person.converter:
      generic:
        # ...
        properties:
          addresses:
            array_converting:
              converter: address.converter

    address.converter:
      generic:
        # ...
```

If only a single property of each array item should be converted, add `source_array_item`:

```yaml
        properties:
          addresses:
            array_converting:
              converter: address.converter
              source_array_item: value
```

There is no new converter but a different populator implementation for this.

### PropertyMappingPopulator & ArrayPropertyMappingPopulator

#### Closure based upon your converter

If you need a Closure which is based upon this Converter (e.g. for PropertyMappingPopulation) you can declare it in this
way and use it in the `PropertyMappingPopulator` configuration as argument for `$mapper`:

```yaml
converter.closure.factory:
  class: \Closure
  factory: [ '\Closure', 'fromCallable' ]
  arguments: [ '@my.converter', 'convert' ]
```

### Conditional Populators

Sometimes you want to populate, but not in every case, only under certain circumstances.
Therefore, we offer the `ConditionalPopulator`.

This populator allows you to define a condition under which the population -
implemented in another populator - should be done.
So, the decoration pattern is used here:

```yaml
my.conditional.populator:
    public: true
    class: Neusta\ConverterBundle\Populator\ConditionalPopulator
    arguments:
        $populator: '@my.populator'
        $condition: '@my.condition'
```

The condition is a Closure that could e.g., be created by the `ClosureFactory`:

```yaml
my.condition:
    class: Closure
    factory: [Closure, fromCallable]
    arguments: [['@My\Condition', 'checkCondition']]
```

> [!TIP]
> If your [Service is invokable, and you use Symfony 6.1+](https://symfony.com/doc/6.4/service_container.html#injecting-a-closure-as-an-argument) you don't need to specify an extra service. 
> You can directly use it like this:
>
> ```yaml
> my.conditional.populator:
>     public: true
>     class: Neusta\ConverterBundle\Populator\ConditionalPopulator
>     arguments:
>         $populator: '@my.populator'
>         $condition: !closure '@my.condition'
> ```

### Configuring a condition

Instead of wiring the decorator by hand you can add a `condition` to a populator declared under
`neusta_converter.populators`:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  populators:
    person.address.populator:
      converting:
        target: address
        converter: address.converter
        condition:
          property: ageInYears   # property of the source object ...
          property_base: source  # ... or set to "target" for the target object
          expected_value: 18
```

Alternatively, use a [Symfony ExpressionLanguage](https://symfony.com/doc/current/components/expression_language.html)
expression, with `source`, `target` and `context` available as variables:

```yaml
        condition:
          expression: 'source.getAgeInYears() >= 18'
```

`property` and `expression` are mutually exclusive.

## Extending the configuration

The type keyword below a converter or populator id is an extension point: other bundles - or your own
app - can register additional types, which then become configurable under `neusta_converter` like the
built-in ones.

### Adding your own converter and populator types

> [!WARNING]
> The factory interfaces below are marked `@experimental` and are **not** covered by the backward
> compatibility promise yet. Their shape will be settled once the first real consumers have exercised
> them — most likely by replacing the loose parameters with a factory context object (see issue #108).
> Expect one
> mechanical adjustment in a future minor if you implement them today.

A converter type is a `Neusta\ConverterBundle\DependencyInjection\Converter\ConverterFactory`. It
contributes its own slice of the config tree and creates the service definitions for it, so its
converter class is free to have whatever constructor it likes:

```php
use Neusta\ConverterBundle\DependencyInjection\Converter\ConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\FactoryRegistry;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class CachingConverterFactory implements ConverterFactory
{
    public function getType(): string
    {
        return 'caching';
    }

    // configures `neusta_converter.converters.<id>.caching`
    public function addConfiguration(ArrayNodeDefinition $node, FactoryRegistry $factories): void
    {
        $node
            ->children()
                ->scalarNode('key_factory')->isRequired()->end()
            ->end()
        ;
    }

    public function create(ContainerBuilder $container, string $id, array $config, FactoryRegistry $factories): void
    {
        $container->register($id, CachingConverter::class)
            ->setPublic(true)
            ->setArguments(['$keyFactory' => new Reference($config['key_factory'])]);
    }
}
```

A populator type is a `Neusta\ConverterBundle\DependencyInjection\Populator\PopulatorFactory`. If
your populator can be expressed as a `PropertyMappingPopulator` with a custom mapper, extend
`PropertyMappingPopulatorFactory` and only override `getType()` and `getMapperDefinition()` - you then
inherit the shared `source` / `default` / `skip_null` options:

```php
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyMappingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyPopulatorFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

final class LocalizedPropertyMappingPopulatorFactory extends PropertyMappingPopulatorFactory implements PropertyPopulatorFactory
{
    public function getType(): string
    {
        return 'localized';
    }

    // configures the type-specific options
    public function addPropertyConfiguration(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->scalarNode('locale')->defaultNull()->end()
            ->end()
        ;
    }

    protected function getMapperDefinition(array $config): Definition
    {
        return (new Definition(LocalizedMapper::class))
            ->setArguments(['$locale' => $config['locale']]);
    }
}
```

Implementing `PropertyPopulatorFactory` in addition to `PopulatorFactory` is what makes a populator
type usable *inside* a converter's `properties`, not just as a standalone entry under `populators`.

### Registering the types

Register your factories from your bundle's `build()` method:

```php
use Neusta\ConverterBundle\DependencyInjection\NeustaConverterExtension;
use Neusta\ConverterBundle\NeustaConverterBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class MyBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $extension = $container->getExtension(NeustaConverterBundle::ALIAS);
        \assert($extension instanceof NeustaConverterExtension);

        $extension->addConverterFactory(new CachingConverterFactory());
        $extension->addPopulatorFactory(new LocalizedPropertyMappingPopulatorFactory());
    }
}
```

The kernel registers *all* extensions before it calls *any* bundle's `build()`, so this works no
matter in which order the bundles are registered.

Both types are now configurable:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  converters:
    user.converter:
      caching:
        key_factory: YourNamespace\UserKeyFactory

    person.converter:
      generic:
        target: YourNamespace\Person
        properties:
          description:
            localized:
              locale: de
```

> [!NOTE]
> Exactly one type may be set per converter, per populator and per property.

> [!NOTE]
> `context_configurators` and `decorators` are reserved converter type names; `decorators` is also
> reserved for populators. They are keys the bundle itself puts as a sibling of the type key (see
> [Context](#context) and [Decorating converters and populators](#decorating-converters-and-populators-planned)
> below), so a converter/populator type registered under one of them is rejected immediately, with a
> clear error, instead of silently colliding with a future bundle version that adds one.

### Decorating converters and populators (planned)

> [!WARNING]
> Not implemented yet - documented here so the config shape is settled before the first decorator
> ships, because changing it afterwards would be a second breaking change.

Some converters and populators do not *replace* the conversion but wrap it: caching, logging, or the
existing `ConditionalPopulator`. Those will get their own factory interfaces
(`DecoratingConverterFactory` / `DecoratingPopulatorFactory`) and their own **ordered** config key,
next to the single type key:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  converters:
    person.converter:
      generic:            # exactly one type, as today
        target: YourNamespace\Person
        properties:
          email: ~
      decorators:         # an ordered sequence: the first entry is the outermost decorator
        - cached: { key_factory: YourNamespace\PersonKeyFactory }
        - logged: ~
```

It is deliberately a **sequence** rather than sibling keys next to `generic`. Sibling keys would have
to carry their order implicitly through the YAML key order, and that does not survive: Symfony's
config component appends defaults in `ArrayNode::finalizeValue()` and appends keys coming from a
second config file in `ArrayNode::mergeValues()`. Key order therefore stops matching the written
order as soon as the configuration is split across several files or environments - an implicit
contract that would silently break. A sequence makes the order explicit and mergeable.

`condition:` (see [Configuring a condition](#configuring-a-condition)) already works today as a key of
its own on a populator; it will be moved onto this mechanism once it exists, so conditional
population, caching and decorating converters all end up following one rule instead of three.
`context_configurators` (see [Context](#context)) is the converter-side counterpart: it already
decorates the converter with `ConverterWithDefaultContext` today and will move onto `decorators:` the
same way.

## Context

Sometimes you will need parameterized conversion which is not depending on the objects themselves.
Think about environment parameters, localization or other specifications of your app.

This information is put inside a `Context` object and passed along with your conversion. Unlike a plain
key/value bag, `Context` stores at most one instance per class, so each piece of context data gets its own small,
typed class:

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
use Neusta\ConverterBundle\Context;

$ctx = Context::create(new LocaleContext('de'));
$person = $this->converter->convert($user, $ctx);
```

`Context` is immutable — every mutating method returns a new instance:

* `with(object ...$objects)` — returns a new `Context` with the given objects added (or replaced, by class).
* `without(object|class-string $value)` — returns a new `Context` with the given object/class removed.
* `has(class-string $class)` — checks whether an instance of that class is present.
* `get(class-string $class)` — returns the instance, throwing `InvalidArgumentException` if it's not present.
* `tryGet(class-string $class)` — returns the instance, or `null` if it's not present — handy for an optional
  context with a fallback: `$ctx->tryGet(LocaleContext::class)->locale ?? 'en'`.

The factory and the populators are called with that context as well, so they can read and use it:

```php
// inside the Populator implementation
public function populate(object $target, object $source, ?object $ctx = null): void
{
    if ($ctx instanceof Context && $ctx->has(LocaleContext::class)) {
        $locale = $ctx->get(LocaleContext::class)->locale;
    }
}
```

You can use the context in factories and populators with custom implementation, but it is also possible to use the
property mapping like described in section [mapping context](#mapping-context).

### Default context via `ContextConfigurator`

If part of the context (e.g. the current locale, tenant, or feature flags) should be available to a converter
regardless of what the caller passes in, implement `ContextConfigurator`:

```php
namespace App\Context;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Context\ContextConfigurator;

final class LocaleContextConfigurator implements ContextConfigurator
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function configureContext(Context $ctx): Context
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';

        return $ctx->with(new LocaleContext($locale));
    }
}
```

Register it as a service and reference it either globally (applied to every converter) or per converter:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  # applied to all converters
  context_configurators:
    - App\Context\LocaleContextConfigurator

  converters:
    person.converter:
      generic:
        target: YourNamespace\Person
        context:
          locale: App\Context\LocaleContext
      # applied only to this converter, in addition to the global ones
      context_configurators:
        - App\Context\TenantContextConfigurator
```

`context_configurators` is a sibling of the type key (`generic` above), not nested inside it: it drives the
`ConverterWithDefaultContext` decorator, which wraps the converter service by id regardless of its type — so it
works the same way for a custom converter type as it does for `generic`.

As soon as a converter declares `context_configurators` (globally or locally), it automatically builds and uses a
default `Context` from those configurators — even if the caller doesn't pass one to `convert()`. If the caller does
pass a `Context`, it is merged on top of the default one, taking precedence per class.

All configurators are re-run on every `convert()` call — the default `Context` is never cached across calls, so a
configurator reading dynamic or per-request state (e.g. the current locale) always sees up-to-date data. This also
means a converter nested inside another one (e.g. via `ArrayConvertingPopulator`) re-runs its configurators once
per source array item, seeded with whatever `Context` the caller or an outer converter already resolved.

> [!TIP]
> If a configurator reads or produces expensive state, check `$ctx->has(YourClass::class)` first and return `$ctx`
> unchanged when it's already set. For a class an ancestor already provided, your own value would be discarded
> anyway (the ancestor's always takes precedence), so the check lets you skip that work. For a class only your
> configurator produces, there's nothing to check for — it's kept as usual either way.

> [!IMPORTANT]
> Once a converter uses `context_configurators`, callers must pass either nothing or a `Context` instance — the
> deprecated `GenericContext` (or any other non-`Context` object) is rejected with an `InvalidArgumentException`.

> [!IMPORTANT]
> Once a converter uses `context_configurators`, every entry under its `context:` mapping must set a `class`
> (nested key or class-string shortcut) — this is validated when the container is compiled.

### Migrating from `GenericContext`

`Neusta\ConverterBundle\Converter\Context\GenericContext` — the old key/value context bag — is deprecated in favor
of `Context` and will be removed in the next major version. See [UPGRADE.md](../UPGRADE.md) for migration
instructions.
