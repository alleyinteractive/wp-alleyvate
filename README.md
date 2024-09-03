# Alleyvate

Alleyvate contains baseline customizations and functionality for WordPress sites that are essential to delivering a project meeting Alley's standard of quality.

## Installation

Install the latest version with:

```bash
composer require alleyinteractive/wp-alleyvate
```

## Basic usage

Alleyvate is a collection of distinct features, each of which is enabled by default. Each feature has a handle, and sites can opt out of individual features with the `alleyvate_load_feature` or `alleyvate_load_{$handle}` filters. Features load on the `after_setup_theme` hook, so your filters must be in place before then.

### Disabling Features

The intention of this plugin is that all features should be on by default, unless there is a good reason to turn them off. For example most sites will want to have the `disable_comments` feature turned on, unless a site is actually using WordPress comments, in which case it should be turned off.

To disable a feature, use the `alleyvate_load_{$feature_name}` filter and return `false`. For example, to tell Alleyvate to _not_ disable comments:

```php
add_filter( 'alleyvate_load_disable_comments', '__return_false' );
```

## Features

Each feature's handle is listed below, along with a description of what it does.

### `cache_slow_queries`

This feature caches/optimizes slow queries to the database to improve
performance. It is enabled by default and currently includes the following slow
queries with the relevant filters to disable them:

- `alleyvate_cache_months_dropdown`: The dropdown for selecting a month in the post list table.

### `clean_admin_bar`

This feature removes selected nodes from the admin bar.

### `disable_apple_news_non_prod_push`

This feature disables pushing to Apple News when not on a production environment. This is determined by setting the `WP_ENVIRONMENT_TYPE` environment variable, or the `WP_ENVIRONMENT_TYPE` constant.

### `disable_attachment_routing`

This feature disables WordPress attachment pages entirely from the front end of the site.

### `disable_block_editor_rest_api_preload_paths`

This feature enhances the stability and performance of the block edit screen by disabling the preloading of Synced
Patterns (Reusable Blocks). Typically, preloading triggers `the_content` filter for each block, along with
additional processing. This can lead to unexpected behavior and performance degradation, especially on sites with
hundreds of synced patterns. Notably, an error in a single block can propagate issues across all block edit screens.
Disabling preloading makes the system more resilient—less susceptible to cascading failures—thus improving overall
admin stability. For technical details on how WP core implements preloading, refer to
`wp-admin/edit-form-blocks.php.`

### `disable_comments`

This feature disables WordPress comments entirely, including the ability to post, view, edit, list, count, modify settings for, or access URLs that are related to comments completely.

### `disable_custom_fields_meta_box`

This feature removes the custom fields meta box from the post editor.

### `disable_dashboard_widgets`

This feature removes clutter from the dashboard.

### `disable_deep_pagination`

This feature restricts pagination queries to, at most, 100 pages by default. This value is filterable using the `alleyvate_deep_pagination_max_pages` filter, or by passing the  `__dangerously_set_max_pages` argument to `WP_Query`.

```php
// An example.
$query = new WP_Query(
  [
    'paged' => 102,
    '__dangerously_set_max_pages' => 150,
  ]
);
```

### `disable_file_edit`

This feature prevents the editing of themes and plugins directly from the admin.

Such editing can introduce unexpected and undocumented code changes.

### `disable_pantheon_constant_overrides`

This feature prevents Pantheon environments from forcing CLI and Cron runs to use the `WP_HOME` or `WP_SITEURL` constants,
which have been shown to force those environments to use an insecure protocol at times.

### `disable_password_change_notification`

This feature disables sending password change notification emails to site admins.

### `disable_site_health_directories`

This feature disables the site health check for information about the WordPress directories and their sizes.

### `disable_sticky_posts`

This feature disables WordPress sticky posts entirely, including the ability to set and query sticky posts.


### `disable_trackbacks`

This feature disables WordPress from sending or receiving trackbacks or pingbacks.

### `force_two_factor_authentication`

This feature forces users with `edit_posts` permissions to use two factor authentication (2fa) for their accounts.

### `login_nonce`

This feature adds a nonce to the login form to prevent CSRF attacks.

### `prevent_framing`

This feature prevents the site from being framed by other sites by outputting a
`X-Frame-Options: SAMEORIGIN` header. The header can be disabled by filtering
`alleyvate_prevent_framing_disable` to return true. The value of the header can
be filtered using the `alleyvate_prevent_framing_x_frame_options` filter.

The feature can also output a `Content-Security-Policy` header instead of
`X-Frame-Options` by filtering `alleyvate_prevent_framing_csp` to return true.
By default, it will output `Content-Security-Policy: frame-ancestors 'self'`.
The value of the header can be filtered using
`alleyvate_prevent_framing_csp_frame_ancestors` to filter the allowed
frame-ancestors. The entire header can be filtered using
`alleyvate_prevent_framing_csp_header`.

### `redirect_guess_shortcircuit`

This feature stops WordPress from attempting to guess a redirect URL for a 404 request.

The underlying behavior of `redirect_guess_404_permalink()` often confuses clients, and its database queries are non-performant on larger sites.

### `remove_shortlink`

This feature removes the shortlink from the head of the site. By default,
WordPress adds a shortlink to the head of the site, which is not used by most
sites.

### `user_enumeration_restrictions`

This feature requires users to be logged in before accessing data about registered users that would otherwise be publicly accessible. Its handle is `user_enumeration_restrictions`.

WordPress core ["doesn't consider usernames or user IDs to be private or secure information"][1] and therefore allows users to be listed through some of its APIs.

Our clients tend to not want information about the registered users on their sites to be discoverable; such lists can even disclose Alley's relationship with a client.

## About

### License

[GPL-2.0-or-later](https://github.com/alleyinteractive/wp-alleyvate/blob/main/LICENSE)

### Maintainers

[Alley Interactive](https://github.com/alleyinteractive)

[1]: https://make.wordpress.org/core/handbook/testing/reporting-security-vulnerabilities/#why-are-disclosures-of-usernames-or-user-ids-not-a-security-issue
