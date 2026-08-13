# Changelog

## [Unreleased]

### Added

- Custom populator contracts. Populators no longer have to implement the generic `Neusta\ConverterBundle\Populator`
  interface, whose `$target`, `$source` and `$ctx` parameters are all typed as `object`. Instead you can define your
  own interface with real type hints, so IDEs offer autocompletion and type checks for the actual source and target
  classes.

  A contract interface is annotated with `#[AsPopulatorContract]` and declares exactly one method whose parameters
  are marked with `#[Source]`, `#[Target]` and — optionally — `#[Context]`:

  ```php
  #[AsPopulatorContract]
  interface UserToPersonPopulator
  {
      public function populatePerson(
          #[Source] User $user,
          #[Target] Person $person,
          #[Context] ?GenericContext $context,
      ): void;
  }
  ```

  Populators implement that interface without repeating the attributes and are configured exactly like regular ones.
  The parameters may be declared in **any order** — the attributes, not the positions, decide which argument goes
  where. Contracts are resolved at container-compile time, so there is no runtime overhead.
- New attributes in `Neusta\ConverterBundle\Populator\CustomContract\Attribute`:
  - `AsPopulatorContract` — marks an interface as a populator contract. Required.
  - `Source`, `Target`, `Context` — mark the parameters of the contract method. `Context` must be nullable.

  A contract must declare exactly one method with exactly one `Source` and one `Target` parameter and at most one
  `Context` parameter. A populator may implement only one contract; an interface extending a contract is fine, the
  most specific one is used. Violations are reported at container-compile time.
- Contract populators are wrapped in a service registered under the id of your populator service suffixed with
  `.populator`. Converters registered through the `neusta_converter` configuration pick this up automatically;
  reference that id if you wire a converter as a plain Symfony service yourself.
- `neusta:converter:debug` lists contract populators alongside regular ones, showing your own populator class.

See [docs/usage.md](docs/usage.md#custom-populator-contracts) for details.
