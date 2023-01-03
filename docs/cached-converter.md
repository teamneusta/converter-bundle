## Conversion with Caching

In some situations - especially if you are transforming objects with relation to objects - it may be helpful
to use caching to avoid conversion of same object instances again and again.

Therefore we offer a simple version of `DefaultCachedConverter`.

Before you can directly use it you have to implement a cache key strategy of your source objects;
i.e. you have to determine how one can differentiate the source objects.

This is the task of the `CacheKeyFactory`.

### CacheKeyFactory

May be in our User example there will be a unique user ID (uuid) then your `CacheKeyFactory`
should be the following:

```php
use Neusta\ConverterBundle\CacheManagement\CacheKeyFactory;

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

### Symfony Configuration of cached conversion

To put things together declare the following cached converter in your Symfony config:

```yaml
person.converter:
  parent: 'default.converter.with.cache'
  public: true
  arguments:
    $factory: '@YourNameSpace\PersonFactory'
    $populators:
      - '@YourNameSpace\PersonNamePopulator'
    $cacheManagement: '@Neusta\ConverterBundle\CacheManagement\DefaultCacheManagement'

Neusta\ConverterBundle\CacheManagement\DefaultCacheManagement:
  arguments:
    $keyFactory: '@YourNameSpace\UserKeyFactory'
```

The `DefaultCacheManagement` is offering a simple array-based cache of converted objects which is using the $keyFactory
to determine the cache key. This allows you to implement very domainspecific identifications of your object conversions.

The `DefaulCachedConverter` is using the `DefaultCacheManagement` component and always reads first from cache and if the
target object can not be found it will be written into the cache before returning.

## Why?!

May be you will ask yourself why not implement the Converter-and-Populator-pattern by yourself and use this extension
instead. The answer is quite simple:

It's a pattern and it should be done always in the same manner so that other developers will recognize the structure
and be able to focus on the real important things:
the populations.

But if you find your "own" way you can not expect others to come into your mind asap.

Of course if you miss something here, just feel free to add it but be aware of compatibility of older
versions.