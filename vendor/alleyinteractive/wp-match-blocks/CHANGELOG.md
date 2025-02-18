# Changelog

This library adheres to [Semantic Versioning](https://semver.org/) and [Keep a CHANGELOG](https://keepachangelog.com/en/1.0.0/).

## Unreleased

Nothing yet.

## 3.1.0

### Changed

* Reduce uses of validators within validators.

## 3.0.0

### Changed

* "Classic" blocks, which have inner HTML but no block name, are no longer considered empty.

## 2.0.1

### Fixed

- Missing `alleyinteractive/composer-wordpress-autoloader` dependency.

## 2.0.0

### Added

- `with_innerhtml` parameter for matching blocks by their inner HTML. Includes companion `\Alley\WP\Validator\Block_InnerHTML` validator.
- `has_innerblocks` parameter for matching blocks by whether they have inner blocks. Includes companion `\Alley\WP\Validator\Block_InnerBlocks_Count` validator.
- `is_valid` parameter for matching blocks by custom validation logic.
- `CONTAINS` and `NOT CONTAINS` (case-sensitive), and `LIKE` and `NOT LIKE` (case-insensitive) operators to `attrs` parameter.

### Changed

- Passing a single block instance will return matches within its inner blocks.

### Removed

- PHP 7.4 support.

## 1.0.1

### Fixed

- Incorrect namespace in `README.md` examples.

## 1.0.0

Initial release.
