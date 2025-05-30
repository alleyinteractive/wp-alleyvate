# Changelog

This library adheres to [Semantic Versioning](https://semver.org/) and [Keep a CHANGELOG](https://keepachangelog.com/en/1.0.0/).

## Unreleased

Nothing yet.

## 3.4.0

### Changed

* Minimum PHP version is now 8.2.
* Upgraded to `symfony/http-foundation` v7.

## 3.7.2

### Added

### Changed

* `disable_deep_pagination`: For urls over max pages display 404 template and set header stauts to 410.
* `disable_deep_pagination`: Pagination display accordingly to max pages.

## 3.7.1

### Fixed

* `twitter_embeds`: Fixed an issue where support for x.com embeds would load too late in some cases.

## 3.7.0

### Added

* `noindex_password_protected_posts`: Added a feature to add noindex to the robots meta tag content for password-protected posts.

### Changed

* Removed fsockopen backstop option for Twitter/X oEmbeds.
* On login nonce failure, redirect back to the login page with an error message.

## 3.6.0

### Added

* Added a feature to improve Twitter/X oEmbed handling.

## 3.5.2

* Optimize some unit tests that created a lot of posts.

## 3.5.1

* Added support for `alleyinteractive/wp-type-extensions` v3.

## 3.5.0

### Added

* Added build release scripts and GitHub Actions for automated releases (used for this release).
* Added a feature to disable XML-RPC (and removes all methods) for all requests that come from IPs that are not known Jetpack IPs.

### Fixed

* `login_nonce`: Fixed issue where loading cached version of login page would store invalid nonce.

## 3.4.0

### Changed

* The `disable_attachment_routing` feature now also disables the automatic redirect from an attachment to its corresponding file URL.

## 3.3.0

### Added

* `disable_site_health_directories`: Added a feature to disable the site health check for information about the WordPress directories and their sizes.

## 3.2.0

### Added

* `disable_apple_news_non_prod_push`: Added a feature to disable pushing to Apple News when not on a production environment.

### Fixed

* `force_two_factor_authentication`: Fixed an infinite loop issue on VIP sites.

## 3.1.0

### Added

* `disable_pantheon_constant_overrides`: Added a feature to disable forcing use of `WP_SITEURL` and `WP_HOME` on Pantheon environments.
* `force_two_factor_authentication`: Added a feature to force Two Factor Authentication for users with `edit_posts` permissions.
* `disable_deep_pagination`: Added a feature to restrict pagination to at most 100 pages, by default. This includes a filter `alleyvate_deep_pagination_max_pages` to override this limit, as well as a new `WP_Query` argument to override the limit: `__dangerously_set_max_pages`.
* `disable_block_editor_rest_api_preload_paths` Added a feature to disable preloading Synced Patterns (Reusable
  Blocks) on the block edit screen to improve performance on sites with many patterns.

## 3.0.1

### Changed

* Removed `composer/installers` from Composer dependencies.

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
