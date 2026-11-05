# Changelog

All notable changes to `alleyinteractive/wordpress-autoloader` will be
documented in this file.

## [Unreleased]

## 1.2.1

- Reverts the file rename from `Autoload.php` to `class-autoload.php` that is causing issues downstream.

## 1.2.0

- Dropped PHP 8.0 support, added testing for 8.4. New minimum PHP version is 8.1.
- Renamed `is_missing_class()` to `isMissingClass()` and `get_apcu_prefix()` to `getApcuPrefix()`.
- Deprecated `set_apcu_prefix()` but method still exists for backwards
  compatibility. `setApcuPrefix()` is the new method name.

## 1.1.1 - 2022-08-31

- Ensure file is still loaded with APCu.

## 1.1.0 - 2022-08-09

- Adding APCu caching of autoloaded classes.
- Adds check to prevent multiple failed calls to autoload a class.

## 1.0.0 - 2022-05-25

## 0.2.0

- Supporting PHP 8.1
- Removing `preg_replace` with `str_*` functions.

## 0.1.2

- Small performance improvement.

## 0.1.1

- Ensure autoloader root path always has a trailing slash.

## 0.1.0

- Initial release.
