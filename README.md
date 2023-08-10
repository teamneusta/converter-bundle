# Converter Bundle

A default implementation of the Converter & Populator design pattern.

## Installation

Run `composer require teamneusta/converter-bundle` to install the bundle.

Add the following line into your `bundles.php` file to activate it:

```php
...
return [
    ...
    Neusta\ConverterBundle\NeustaConverterBundle::class => ['all' => true],
    ...
];
```

This is important for preloading the default configuration of provided converter implementations which can be reused
and simplify your code and further updates.

## [Usage](docs/usage.md)

## [Development](docs/development.md)
