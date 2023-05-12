# Alleyvate

Alleyvate contains baseline customizations and functionality for WordPress sites that are essential to delivering a project meeting Alley's standard of quality.

## Installation

Install the latest version with:

```bash
$ composer require alleyinteractive/wp-alleyvate
```

## Basic usage

Alleyvate is a collection of distinct features, each of which is enabled by default. Each feature has a handle, and sites can opt out of individual features with the `alleyvate_load_feature` or `alleyvate_load_{$handle}` filters. Features load on the `after_setup_theme` hook, so your filters must be in place before then.

## Features

### Disallow File Edit

This feature prevents the editing of themes and plugins directly from the admin.

Such editing can introduce unexpected and undocumented code changes.

### Short-Circuit Redirect Guessing

This feature stops WordPress from attempting to guess a redirect URL for a 404 request. Its handle is `redirect_guess_shortcircuit`.

The underlying behavior of `redirect_guess_404_permalink()` often confuses clients, and its database queries are non-performant on larger sites.

### Restrict User Enumeration

This feature requires users to be logged in before accessing data about registered users that would otherwise be publicly accessible. Its handle is `user_enumeration_restrictions`.

WordPress core ["doesn't consider usernames or user IDs to be private or secure information"][1] and therefore allows users to be listed through some of its APIs.

Our clients tend to not want information about the registered users on their sites to be discoverable; such lists can even disclose Alley's relationship with a client.

## About

### License

[GPL-2.0-or-later](https://github.com/alleyinteractive/wp-alleyvate/blob/main/LICENSE)

### Maintainers

[Alley Interactive](https://github.com/alleyinteractive)

[1]: https://make.wordpress.org/core/handbook/testing/reporting-security-vulnerabilities/#why-are-disclosures-of-usernames-or-user-ids-not-a-security-issue
