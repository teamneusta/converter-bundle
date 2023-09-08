# Converter Bundle

A default implementation of the Converter & Populator design pattern.

## Installation

1. **Require the bundle**

   ```shell
   composer require teamneusta/converter-bundle
   ```

2. **Enable the bundle**

   Add the Bundle to your `config/bundles.php`:

   ```php
   Neusta\ConverterBundle\NeustaConverterBundle::class => ['all' => true],
   ```

   This is important for preloading the default configuration of provided converter implementations which can be reused
   and simplify your code and further updates.

## [Usage](docs/usage.md)

## Contribution

Feel free to open issues for any bug, feature request, or other ideas.

Please remember to create an issue before creating large pull requests.

### Local Development

To develop on local machine, the vendor dependencies are required.

```shell
bin/composer install
```

We use composer scripts for our main quality tools. They can be executed via the `bin/composer` file as well.

```shell
bin/composer cs:fix
bin/composer phpstan
bin/composer tests
```
