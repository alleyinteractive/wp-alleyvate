# Changelog

This library adheres to [Semantic Versioning](https://semver.org/) and [Keep a CHANGELOG](https://keepachangelog.com/en/1.0.0/).

## Unreleased

Nothing yet.

## 5.0.0

### Added

- `Filter_Value` interface.
- `Process` interface.
- `Term` interface.
- `By_Default` feature.
- `Widget_Feature` feature.
- `Widget_Features` feature.
- `Merge_Filter` class.
- `Push_Filter` class.
- PHP 8.5 support.

### Changed

- `Features::include()` no longer accepts a spread of individual `Feature` instances. Use something more specific like `Group` or `Ordered` to include multiple features at once.
- `Post_IDs_Once` class renamed `Memoized_Post_IDs`.
- `WP_CLI_Feature` now hooks into `cli_init` at priority `1`.

### Removed

- All feature implementations under the `Alley\WP\Features\Library` namespace. Copy these directly into your projects to use and modify them.
- `Default_Classname_Block` class.

## 4.1.0

### Changed

- Adjusted `alleyinteractive/wp-plugin-loader` to support version 1.0.

## 4.0.0

### Added

- `Blocks::from_parsed_blocks()` secondary constructor for use with an array of parsed blocks.

### Changed

- `GTM_Script` feature now preconnects to `https://www.googletagmanager.com`.

### Removed

- `Matched_Blocks` class, now part of the [Match Blocks](https://packagist.org/packages/alleyinteractive/wp-match-blocks) library.

## 3.1.0

- Adjusted `alleyinteractive/wp-plugin-loader` to support version 1.0.

## 3.0.0

### Added

- `Features` interface.
- `Effect` feature.
- `Ordered` feature.
- `Each_Replaced` serialized blocks class.
- `Alley\WP\Features\Library` namespace to hold a library of concrete feature implementations.
- `Allowed_Blocks` library feature.
- `Block_Content_Filter` library feature.
- `Plugin_Loader` library feature.

### Changed

- The minimum PHP version is now 8.2.
- `Group` class now implements `Features`.
- `GTM_Script` class moved to feature library.
- `Each_Appended_Blocks` class renamed `Each_Appended`.

### Removed

- `Conditional_Feature` class (use `Effect` instead).
- `Used_Post_IDs` class.

## 2.2.0

### Added

- `GTM_Script` feature.

## 2.1.0

### Changed

- Support use of `WP_CLI_Feature` in WP-CLI packages.

## 2.0.0

### Changed

- `Features` class renamed `Group`.

## 1.0.0

Initial release.
