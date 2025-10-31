# Composer WordPress Autoloader

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alleyinteractive/composer-wordpress-autoloader.svg?style=flat-square)](https://packagist.org/packages/alleyinteractive/composer-wordpress-autoloader)
[![Tests](https://github.com/alleyinteractive/composer-wordpress-autoloader/actions/workflows/tests.yml/badge.svg)](https://github.com/alleyinteractive/composer-wordpress-autoloader/actions/workflows/tests.yml)

Autoload WordPress files configured via Composer that support the [WordPress
Coding
Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
using
[alleyinteractive/wordpress-autoloader](https://github.com/alleyinteractive/wordpress-autoloader).
Will load the autoloaded classes defined in your package and all autoloaded
classes in your dependencies.

## Installation

You can install the package via Composer:

```bash
composer require alleyinteractive/composer-wordpress-autoloader
```

## Usage

```json
{
  "extra": {
    "wordpress-autoloader": {
      "autoload": {
        "My_Plugin_Namespace\\": "src/",
      },
      "autoload-dev": {
        "My_Plugin_Namespace\\Tests\\": "tests/",
      }
    }
  }
}
```

Once added, you can load `vendor/autoload.php` as normal and the autoloader will
handle the rest. If that doesn't work, see [Automatically Injecting WordPress
Autoloader](#automatically-injecting-wordpress-autoloader).

### Automatically Injecting WordPress Autoloader

By default Composer WordPress Autoloader will automatically load the WordPress
autoloader. This is done by adding `src/autoload.php` as an autoloaded file to
Composer. However, this may not always work under some circumstances including
symlinks. When necessary, you can opt to inject the
`vendor/wordpress-autoload.php` file into your `vendor/autoload.php` file. This
is disabled by default and be enabled by setting `inject` to `true` in your
`composer.json`.

```json
{
  "extra": {
    "wordpress-autoloader": {
      "inject": true
    }
  }
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.
