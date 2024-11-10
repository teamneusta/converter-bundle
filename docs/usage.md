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
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Populator;

/**
 * @implements Populator<User, Person, GenericContext>
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
      target: YourNamespace\Person
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
      target: YourNamespace\Person
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
      target: YourNamespace\Person
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

You can use it in your converter config via the `context` keyword:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  converter:
    person.converter:
      target: YourNamespace\Person
      context:
        group: ~
        locale: language
```

Which will populate

`group` (property of the target object)

with `group` (property of the context object)

and

`locale` (property of the target object)

with `language` (property of the context object).

> [!IMPORTANT]
> The context and the target property must be of the same type for this to work.

### Conversion

And now if you want to convert `User`s into `Person`s just type in your code:

```php
/** @var Converter<User, Person, GenericContext> */
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

## Context

Sometimes you will need parameterized conversion which is not depending on the objects themselves.
Think about environment parameters, localization or other specifications of your app.
This information can be put inside a `GenericContext` object and called with your conversion:

```php
$ctx = new \Neusta\ConverterBundle\Converter\Context\GenericContext();
$ctx->setValue('locale', 'de');
...
$target = $this->converter->convert($source, $ctx);
```

The factory and the populators will be called with that context as well, so that they can read and use it:

```php
// inside the Populator implementation
if ($ctx && $ctx->hasKey('locale')) {
    $locale = $ctx->getValue('locale');
}
```

Internally the `GenericContext` is only an associative array, but the interface allows you to adapt your own
implementation of a domain-oriented context and use it in your populators as you like.

You can use the context in factories and populators with custom implementation,
but it is also possible to use the property mapping like described in section [mapping context](#mapping-context).
