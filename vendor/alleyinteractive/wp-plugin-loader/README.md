# WP Plugin Loader

Code-enabled WordPress plugin loading package.

## Installation

You can install the package via Composer:

```bash
composer require alleyinteractive/wp-plugin-loader
```

## Usage

Load the package via Composer and use it like so:

```php
use Alley\WP\WP_Plugin_Loader;

new WP_Plugin_Loader( [
	'plugin/plugin.php',
	'plugin-name-without-file',
] );
```

The plugin loader will load the specified plugins, be it files or folders under
`plugins`/`client-mu-plugins`, and mark them as activated on the plugins screen.
You can pass files or plugin folders that the package will attempt to determine
the main plugin file from and load.

See [APCu Caching](#apcu-caching) for more information on caching.

### Plugin Directories

Out of the box, the package will attempt to load your plugin from
`wp-content/plugins`. When it is found, the package will attempt to load your
plugin from `wp-content/client-mu-plugins`. For non-WordPress VIP sites, the
plugin will also load plugins from `wp-content/mu-plugins`.

### Preventing Activations

The package supports preventing activations of plugins via the plugins screen
(useful to fully lock down the plugins enabled on site):

```php
use Alley\WP\WP_Plugin_Loader;

( new WP_Plugin_Loader( [ ... ] )->prevent_activations();
```

Plugin activations will be prevented on the plugin screen as well as with a
capability check.

### APCu Caching

When a plugin is loaded by a directory name the package will attempt to
determine the main plugin file from the directory. This can be a semi-expensive
operation that can be cached with APCu. To enable caching, pass `$cache` to the
constructor with a boolean or string prefix:

```php
use Alley\WP\WP_Plugin_Loader;

new WP_Plugin_Loader( plugins: [ ... ], cache: true );

new WP_Plugin_Loader( plugins: [ ... ], cache: 'my-prefix' );
```

Note: caching will only be enabled if APCu is available.

## Testing

Run `composer test` to run tests against PHPUnit and the PHP code in the plugin.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

This project is actively maintained by [Alley
Interactive](https://github.com/alleyinteractive). Like what you see? [Come work
with us](https://alley.com/careers/).

- [Sean Fisher](https://github.com/srtfisher)
- [All Contributors](../../contributors)

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.
