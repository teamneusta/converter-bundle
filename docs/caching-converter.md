## Conversion with Caching

In some situations - especially if you are transforming objects with relation to objects - it may be helpful
to use caching to avoid conversion of same object instances again and again.

Therefore, we offer a `CachingConverter`.

Before you can directly use it you have to implement a cache key strategy for your source objects;
i.e. you have to determine how one can differentiate the source objects.

This is the task of the `CacheKeyFactory`.

### CacheKeyFactory

Maybe in our `User` example there will be a unique user ID (uuid) then your `CacheKeyFactory`
should be the following:

```php
use Neusta\ConverterBundle\Converter\Cache\CacheKeyFactory;

/**
 * @implements CacheKeyFactory<User>
 */
class UserKeyFactory implements CacheKeyFactory
{
    public function createCacheKey(object $source): string
    {
        return (string) $source->getUuid();
    }
}
```

### Configuration of cached conversion

To put things together register the cache key factory as a service:

```yaml
# config/services.yaml
services:
  ...
  YourNamespace\UserKeyFactory: ~
```

And then add it to the converter in your package config via the `cached` keyword:

```yaml
# config/packages/neusta_converter.yaml
neusta_converter:
  converter:
    person.converter:
      ...
      cached:
        key_factory: YourNamespace\UserKeyFactory
```

This will use the  `InMemoryCache`, which is offering a simple array-based cache of converted objects
using the `key_factory` to determine the cache key. This allows you to implement very domain-specific identifications
of your object conversions.

> Note: You can also use a custom implementation of the `Cache` interface by using the `service`
> instead of the `key_factory` keyword.

## Why?!

Maybe you will ask yourself why not implement the Converter-and-Populator-pattern by yourself and use this extension
instead. The answer is quite simple:

It's a pattern, and it should be done always in the same manner so that other developers will recognize the structure
and be able to focus on the real important things:
the populations.

But if you find your "own" way you can not expect others to come into your mind asap.

Of course if you miss something here, just feel free to add it but be aware of compatibility with older
versions.
