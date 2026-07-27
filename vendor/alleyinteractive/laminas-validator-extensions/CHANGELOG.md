# Changelog

This library adheres to [Semantic Versioning](https://semver.org/) and [Keep a CHANGELOG](https://keepachangelog.com/en/1.0.0/).

## Unreleased

Nothing yet.

## 2.1.1

### Fixed

- Array-to-string conversion in `ContainsString`.

## 2.1.0

### Changed

* Reduce uses of validators within validators.

## 2.0.0

### Added

- `FreeformValidator` abstract class.
- `ContainsString`, `DivisibleBy`, `FastFailValidatorChain`, `ValidatorByOperator`, and `WithMessage` validators.

### Changed

- `BaseValidator` was renamed `ExtendedAbstractValidator` to emphasize its connection to the Laminas `AbstractValidator` now that this library has multiple base validators.
- The failure message returned by `Not::getMessages()` now has the identifier `notValid`.

### Fixed

- `Not::getMessages()` returned failure messages before first call to `::isValid()`.
- `Not::getMessages()` returned an indexed array of messages.
- `Comparison` and `Type` referenced incorrect failure message keys when validating options.

### Removed

- PHP 7.4 support.

## 1.1.0

### Added

- `Not` and `AnyValidator` validators.

### Fixed

- Incorrect value being passed to `BaseValidator::testValue()` in `BaseValidator::isValid()`.

## 1.0.0

Initial release.
