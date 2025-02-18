# Changelog

All notable changes to `WP Plugin Loader` will be documented in this file.

## 0.1.6 - 2024-12-17

- When a plugin is not found, exit with a status code of 1 and send a `500`
  status code header. This will prevent a fatal error from being cached

## 0.1.5 - 2024-09-25

- Ensure that the default cache key is unique to each installation. Previously
  caching could be polluted across different installations on the same server.

## 0.1.4 - 2024-04-24

- Fix to actually allow caching to be enabled.

## 0.1.3 - 2024-02-14

- Changes class from `Alley\WP\WP_Plugin_Loader\WP_Plugin_Loader` to
  `Alley\WP\WP_Plugin_Loader`.
- Account for some one-off plugins that don't follow the standard naming conventions.

# 0.1.2 - 2023-07-25

- Ensure a plugin can be discovered from mu-plugins on a non-VIP environment.

## 0.1.1 - 2023-07-24

- Added APCu caching for plugin folder lookups.

## 0.1.0 - 2023-07-22

- Initial release
