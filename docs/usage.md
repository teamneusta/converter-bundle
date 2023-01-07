## Usage

After the bundle is activated you can directly use it by implementing a factory and a populator for your target and
source types.

Imagine your source type is `User`:

```php
class User
{
    private string $firstname;
    private string $lastname;
    private int $uuid;
    
    // with getters and fluent setters
}
```

and your target type is `Person`:

```php
class Person
{
    private string $fullName;

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }
}
```

and your task is to transform a given User instance into a Person instance.
Of course you can do it by instantiating a new Person and calling associated getters and setters in your code.
But - you shouldn't...why?

There are a lot of reasons but at least the most important is:

Separation of Concerns.

You should use the Converter-and-Populator-pattern. But how?!

Implement the following three artifacts:

### Factory

Implement a comfortable factory for your target type:

```php
use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Factory\TargetTypeFactory;

/**
 * @implements TargetTypeFactory<Person>
 */
class PersonFactory implements TargetTypeFactory
{
    public function create(?ConverterContext $ctx = null): Person
    {
        return new Person();
    }
}
```

Skip thinking about the converter context at the moment. It will help you...
may be not now but in a few weeks. You will see.

### Populators

Implement one or several populators:

```php
use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Populator\Populator;

/**
 * @implements Populator<User, Person>
 */
class PersonNamePopulator implements Populator
{
    public function populate(object $target, object $source, ?ConverterContext $ctx = null): void
    {
        $separator = ' ';
        $target->setFullName($source->getFirstname() . $separator . $source->getLastname());
    }
}
```

As you can see implementation here is quite simple - just concatenation of two attributes.
But however transformation will become more and more complexe it should be done in a testable,
separated Populator or in several of them.

### Symfony configuration

To put things together declare the following converter in your Symfony config:

```yaml
person.converter:
  parent: 'neusta_converter.default_converter'
  public: true
  arguments:
    $factory: '@YourNamespace\PersonFactory'
    $populators:
      - '@YourNamespace\PersonNamePopulator'
      # additional populators may follow 
```

### Conversion

And now if you want to convert `Users` into `Persons` just type in your code:

```php
/** @var Converter<User,Person> */
$converter = $this->getContainer()->get('person.converter');
...
$person = $this->converter->convert($user);
```

Conversion done.

## ConverterContext

Sometimes you will need parameterized conversion which is not depending on the objects themselves.
Think about environment parameters, localization or other specifications of your app.
This information can be put inside a simple `ConverterContext` object and called with your conversion:

```php
$ctx = new Neusta\ConverterBundle\Converter\DefaultConverterContext();
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

Internally the DefaultConverterContext is only an associative array but the interface allows you to adapt your own
implementation of a domain-oriented context and use it in your populators as you like.

## [Conversion with Caching](cached-converter.md)
