# Changelog

This library adheres to [Semantic Versioning](https://semver.org/) and [Keep a CHANGELOG](https://keepachangelog.com/en/1.0.0/).

## 2.2.0

### Added

* Added a Site Health screen panel with information about the enabled/disabled features of the plugin.

## 2.1.0

### Added

* Added a feature to disable comments.
* Added a feature to disable sticky posts.
* Added a feature to disable trackbacks.
* Added a feature to disallow file editing.

### Fixed

* Fixed a bug with the autoloader where the plugin would fatal if the autoloader didn't exist (if `composer install` was not run).

### Upgrade Notes

Please ensure that if your site is using comments that you turn off the `disable_comments` feature before applying the plugin update to your production site, otherwise comments will break. This version disables comments, but doesn't have a utility for removing them from the database, but that is planned for a future version. Likewise, if your site uses sticky posts for controlling curation, make sure to turn off `disable_sticky_posts` before deploying.

To disable one or more of these features:
* `add_filter( 'alleyvate_load_disable_comments', '__return_false' );`
* `add_filter( 'alleyvate_load_disable_trackbacks', '__return_false' );`
* `add_filter( 'alleyvate_load_disable_sticky_posts', '__return_false' );`

## 2.0.0

### Added

* Added a feature to disable `redirect_guess_404_permalink()`.

### Changed

* Alleyvate is now installed by Composer as a WordPress plugin, not autoloaded.

### Upgrade Notes

* When applying this version, inexact URLs will result in 404s. This may lead to a spike in 404s on the site, which developers should pay attention to after deploying this update. It could be that there are links on the site, or externally, that were just a little bit wrong and were relying on this behavior to get to the right place. If discovered, proper 301 redirects should be put in place for those URLs.

To disable this feature:
* `add_filter( 'alleyvate_load_redirect_guess_shortcircuit', '__return_false' );`

## 1.0.0

Initial release.
