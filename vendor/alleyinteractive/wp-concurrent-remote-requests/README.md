# Concurrent Remote Requests Feature Plugin

Stable tag: 0.1.0

Requires at least: 5.9

Tested up to: 5.9

Requires PHP: 7.4

License: GPL v2 or later

Tags: alleyinteractive, wp-concurrent-remote-requests

Contributors: alleyinteractive, srtfisher

[![Coding Standards](https://github.com/alleyinteractive/wp-concurrent-remote-requests/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/alleyinteractive/wp-concurrent-remote-requests/actions/workflows/coding-standards.yml)
[![Testing Suite](https://github.com/alleyinteractive/wp-concurrent-remote-requests/actions/workflows/unit-test.yml/badge.svg)](https://github.com/alleyinteractive/wp-concurrent-remote-requests/actions/workflows/unit-test.yml)

A WordPress Feature plugin for concurrent HTTP remote requests in WordPress.
Adds namespaced helper functions to make concurrent remote requests. Being
tracked in
https://github.com/alleyinteractive/wp-concurrent-remote-requests/pull/2 and
https://core.trac.wordpress.org/ticket/37459.

## Installation

You can install the package via composer:

```bash
composer require alleyinteractive/wp-concurrent-remote-requests
```

## Usage

Activate the plugin in WordPress and use it like so:

```php
// Supports an array of URLs:
\Alley\WP\Concurrent_Remote_Requests\wp_remote_get(
	[
		'https://alley.co/',
		'https://wordpress.org/',
	]
);

// Or more complex requests.
\Alley\WP\Concurrent_Remote_Requests\wp_remote_request(
	[
		[
			'https://alley.co/',
			[
				'body'   => [ ... ],
				'method' => 'POST',
			],
		],
		[
			'https://wordpress.org/',
			[
				'method' => 'DELETE',
			],
		],
	]
);

```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Sean Fisher](https://github.com/srtfisher)
- [All Contributors](../../contributors)

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.
