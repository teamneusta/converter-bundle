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
  converter:
    person.converter:
      target: 
        class: YourNamespace\Person
        # optionally pre-initialized values for target properties
        properties:
            email: 'mail@me.com'
      populators:
        - YourNamespace\PersonNamePopulator
        # additional populators may follow
```

> [!TIP]
> You can use a custom implementation of the `Converter` interface via the `converter` keyword.
> Its constructor *must* contain *exactly* the two parameters `TargetFactory $factory` and `array $populators`.

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
  converter:
    person.converter:
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
  converter:
    person.converter:
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
the populator which object to read the property from via `objectType`. Use it in your converter config via the
`context` keyword:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  converter:
    person.converter:
      target: 
        class: YourNamespace\Person
      context:
        group: YourNamespace\Context\GroupContext
        locale: YourNamespace\Context\LanguageContext
```

A plain class-string value like above is a shortcut for `objectType: YourNamespace\Context\GroupContext`. Which
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
    objectType: YourNamespace\Context\LanguageContext
    property: language
```

> [!IMPORTANT]
> The context and the target property must be of the same type for this to work.

> [!IMPORTANT]
> If the relevant object (`GroupContext`/`LanguageContext` above) is not present in the `Context` passed to
> `convert()`, the populator silently does nothing and leaves the target property untouched — no default is applied
> and no exception is thrown.

> [!NOTE]
> `objectType` is required as soon as the converter uses `context_configurators` (see [Context](#context)) — this is
> validated when the container is compiled. If the converter has no `context_configurators` and you still pass the
> deprecated `GenericContext` (see [UPGRADE.md](../UPGRADE.md)), you may omit `objectType` and use the short form
> `context: { group: ~, locale: language }`, exactly as before — a string value there is only treated as an
> `objectType` shortcut when it names an existing class.

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
  converter:
    person.converter:
      # ...
      populators:
        - person.address.populator

    address.converter:
      # ...

# ...
person.address.populator:
  class: Neusta\ConverterBundle\Populator\ConvertingPopulator
  arguments:
    $converter: '@address.converter'
    $sourcePropertyName: 'address'
    $targetPropertyName: 'address'
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
  converter:
    person.converter:
      # ...
      populators:
        - person.addresses.populator

    address.converter:
      # ...

# ...
person.addresses.populator:
  class: Neusta\ConverterBundle\Populator\ArrayConvertingPopulator
  arguments:
    $converter: '@address.converter'
    $sourcePropertyName: 'addresses'
    $targetPropertyName: 'addresses'
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

  converter:
    person.converter:
      target: YourNamespace\Person
      # applied only to this converter, in addition to the global ones
      context_configurators:
        - App\Context\TenantContextConfigurator
      context:
        locale:
          objectType: App\Context\LocaleContext
```

As soon as a converter declares `context_configurators` (globally or locally), it automatically builds and uses a
default `Context` from those configurators — even if the caller doesn't pass one to `convert()`. If the caller does
pass a `Context`, it is merged on top of the default one, taking precedence per class.

> [!IMPORTANT]
> Once a converter uses `context_configurators`, callers must pass either nothing or a `Context` instance — the
> deprecated `GenericContext` (or any other non-`Context` object) is rejected with an `InvalidArgumentException`.

> [!IMPORTANT]
> Once a converter uses `context_configurators`, every entry under its `context:` mapping must declare `objectType`
> explicitly — this is validated when the container is compiled.

### Migrating from `GenericContext`

`Neusta\ConverterBundle\Converter\Context\GenericContext` — the old key/value context bag — is deprecated in favor
of `Context` and will be removed in the next major version. See [UPGRADE.md](../UPGRADE.md) for migration
instructions.
