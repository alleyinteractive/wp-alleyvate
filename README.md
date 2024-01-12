# Alleyvate

Alleyvate contains baseline customizations and functionality for WordPress sites that are essential to delivering a project meeting Alley's standard of quality.

## Installation

Install the latest version with:

```bash
$ composer require alleyinteractive/wp-alleyvate
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

### `clean_admin_bar`

This feature removes selected nodes from the admin bar.

### `disable_attachment_routing`

This feature disables WordPress attachment pages entirely from the front end of the site.

### `disable_comments`

This feature disables WordPress comments entirely, including the ability to post, view, edit, list, count, modify settings for, or access URLs that are related to comments completely.

### `disable_custom_fields_meta_box`

This feature removes the custom fields meta box from the post editor.

### `disable_dashboard_widgets`

This feature removes clutter from the dashboard.

### `disable_password_change_notification`

This feature disables sending password change notification emails to site admins.

### `disable_sticky_posts`

This feature disables WordPress sticky posts entirely, including the ability to set and query sticky posts.

### `disable_trackbacks`

This feature disables WordPress from sending or receiving trackbacks or pingbacks.

### `disable_file_edit`

This feature prevents the editing of themes and plugins directly from the admin.

Such editing can introduce unexpected and undocumented code changes.

### `login_nonce`

This feature adds a nonce to the login form to prevent CSRF attacks.

### `prevent_framing`

This feature prevents the site from being framed by other sites by outputting a
`X-Frame-Options: SAMEORIGIN` header. The header can be disabled by filtering
`alleyvate_prevent_framing_disable` to return true. The value of the header can
be filtered using the `alleyvate_prevent_framing_x_frame_options` filter.

### `redirect_guess_shortcircuit`

This feature stops WordPress from attempting to guess a redirect URL for a 404 request.

The underlying behavior of `redirect_guess_404_permalink()` often confuses clients, and its database queries are non-performant on larger sites.

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
