# Changelog

This library adheres to [Semantic Versioning](https://semver.org/) and [Keep a CHANGELOG](https://keepachangelog.com/en/1.0.0/).

## Unreleased

### Added

* `disable_pantheon_constant_overrides`: Added a feature to disable forcing use of `WP_SITEURL` and `WP_HOME` on Pantheon environments.
* `force_two_factor_authentication`: Added a feature to force Two Factor Authentication for users with `edit_posts` permissions.

## 3.0.1

- Removing `composer/installers` from Composer dependencies.

## 3.0.0

### Added

* Added PHPStan to the development dependencies.
* `Alley\WP\Alleyvate\Feature` class implementing the `Alley\WP\Types\Feature` interface.
* `remove_shortlink`: Added a feature to remove the shortlink from the head of the site.
* `cache_slow_queries`: Added a feature to cache slow queries for performance.

### Changed

* The minimum PHP version is now 8.1.
* Feature classes now implement the `Alley\WP\Types\Feature` interface instead of `Alley\WP\Alleyvate\Feature`.
* Unit tests: misc changes and fixes.
* Unit tests: the `$feature` property uses the main feature class for better IDE intelephense support.
* Unit tests: all test cases use `declare( strict_types=1 );`.
* Unit tests: added test to confirm the attachment rewrite rules are removed
* Unit tests: support for `convertDeprecationsToExceptions="true"` added. Tests
  will fail if there are PHP deprecation warnings.

### Removed

* `site_health`: Removed as a dedicated feature and now implemented directly in the plugin.
* `Alley\WP\Alleyvate\Feature` interface.

## 2.4.0

### Added

* `login_nonce`: Added a `no-store` header to the wp-login.php page.
* `prevent_framing`: Added a feature to prevent framing of the site via the
  `X-Frame-Options` header.

## 2.3.1

### Changed

* `login_nonce`: make sure the nonce lifetime is run only for the login action
  as to not affect the other `wp-login.php` actions, e.g: logout.

## 2.3.0

### Added

* `disable_attachment_routing`: Added a feature to disable attachment routing.
* `disable_custom_fields_meta_box`: Added a feature to disable the custom fields meta box.
* `disable_password_change_notification`: Added a feature that disables sending password change notification emails to site admins.

### Changed

* `disable_comments`: Removes the `commentstatusdiv` meta box when comments are
  disabled. Previously, only `commentsdiv` was removed.

## 2.2.1

### Added

* `login_nonce`: Added a feature to add a nonce to wp-login

### Changed

* `disable_comments`: Akismet: Removed the comment spam queue section from the WP dashboard

## 2.2.0

### Added

* Added a feature to remove commonly unused dashboard widgets.
* Added a feature to remove specific admin bar links (comments and themes).
* Added a Site Health screen panel with information about the enabled/disabled features of the plugin.

### Changed

* Upgraded to Alley Coding Standards 2.0.
* Upgraded to Mantle TestKit 0.12.

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
