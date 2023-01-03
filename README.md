# Converter Bundle

A default implementation of the Converter & Populator design pattern.

## Installation

Run `composer require neusta/converter-bundle:^1.0@dev` to activate the bundle

Add the following line into your `bundles.php` file:

```php
...
return [
    ...
    Neusta\ConverterBundle\NeustaConverterBundle::class => ['all' => true],
    ...
];
```

This is important for pre-loading the default configuration of provided converter implementations which can be reused
and simplify your code and further updates.

## [Usage](docs/usage.md)
