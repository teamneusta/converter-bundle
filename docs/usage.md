## Usage

After the bundle is activated you can directly use it by implementing a factory and a populator for your target and
source types.

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

There are a lot of reasons but at least the most important is:

Separation of Concerns.

You should use the Converter-and-Populator-pattern. But how?!

Implement the following three artifacts:

### Factory

Implement a comfortable factory for your target type:

```php
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\TargetFactory;

/**
 * @implements TargetFactory<Person, GenericContext>
 */
class PersonFactory implements TargetFactory
{
    public function create(?object $ctx = null): Person
    {
        return new Person();
    }
}
```

Skip thinking about the converter context at the moment. It will help you...
maybe not now but in a few weeks. You will see.

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

As you can see implementation here is quite simple - just concatenation of two attributes.
But however transformation will become more and more complex it should be done in a testable,
separated Populator or in several of them.

### Configuration

To put things together register the factory and populator as services:

```yaml
# config/services.yaml
services:
  YourNamespace\PersonFactory: ~
  YourNamespace\PersonNamePopulator: ~
```

And then declare the following converter in your package config:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  converter:
    person.converter:
      target_factory: YourNamespace\PersonFactory
      populators:
        - YourNamespace\PersonNamePopulator
        # additional populators may follow
```

> Note: You can use a custom implementation of the `Converter` interface via the `converter` keyword.
> Its constructor must contain the two parameters `TargetFactory $factory` and `array $populators`.

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
      ...
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

> Note: the source and the target property must be of the same type for this to work.

### Conversion

And now if you want to convert `User`s into `Person`s just type in your code:

```php
/** @var Converter<User, Person, GenericContext> */
$converter = $this->getContainer()->get('person.converter');
...
$person = $this->converter->convert($user);
```

Conversion done.

## Context

Sometimes you will need parameterized conversion which is not depending on the objects themselves.
Think about environment parameters, localization or other specifications of your app.
This information can be put inside a simple `GenericContext` object and called with your conversion:

```php
$ctx = new \Neusta\ConverterBundle\Converter\Context\GenericContext();
$ctx->setValue('locale', 'de');
...
$target = $this->converter->convert($source, $ctx);
```

The factory and the populators will be called with that context as well, so that they can read and
use it:

```php
// inside the Populator implementation
if ($ctx && $ctx->hasKey('locale')) {
    $locale = $ctx->getValue('locale');
}
```

Internally the `GenericContext` is only an associative array but the interface allows you to adapt your own
implementation of a domain-oriented context and use it in your populators as you like.
