# Changelog

This library adheres to [Semantic Versioning](https://semver.org/) and [Keep a CHANGELOG](https://keepachangelog.com/en/1.0.0/).

## 2.1.0

### Added

* Added a feature to disable comments.
* Added a feature to disable sticky posts.
* Added a feature to disable trackbacks.
* Added a feature to disallow file editing.

### Fixed

* Fixed a bug with the autoloader where the plugin would fatal if the autoloader didn't exist (if `composer install` was not run).

## 2.0.0

### Added

* Added a feature to disable `redirect_guess_404_permalink()`.

### Changed

* Alleyvate is now installed by Composer as a WordPress plugin, not autoloaded.

## 1.0.0

Initial release.
