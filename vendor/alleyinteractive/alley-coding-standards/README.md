# Alley Coding Standards

[![Example of a badge pointing to the readme standard spec](https://img.shields.io/badge/readme%20style-standard-brightgreen.svg?style=flat-square)](https://github.com/RichardLitt/standard-readme)

This is a PHPCS ruleset for [Alley Interactive](https://alley.com).

## Installation

To use this standard in a project, declare it as a dependency.

```bash
composer require --dev alleyinteractive/alley-coding-standards
```

This will install the latest compatible version of PHPCS, WPCS, and VIPCS to your vendor directory in order to run sniffs locally.

You can also manually add this to your project's `composer.json` file as part of the `require` property:

```json
"require": {
	"alleyinteractive/alley-coding-standards": "^2.0"
}
```

## Usage

To use this standard with `phpcs` directly from your command line, use the command:

```bash
vendor/bin/phpcs --standard=Alley-Interactive .
```

Alternatively, you can set this as a composer script, which will automatically reference the correct version of `phpcs` and the dependent standards.

```json
"scripts": {
	"phpcs" : "phpcs --standard=Alley-Interactive ."
}
```

Then use the following command:

```bash
composer run phpcs
```

You can also pass arguments to the composer phpcs script, following a `--` operator like this:

```bash
composer run phpcs -- --report=summary
```

### Extending the Ruleset

You can create a custom ruleset for your project that extends or customizes
these rules by creating your own `phpcs.xml` file in your project, which
references these rules, like this:

```xml
<?xml version="1.0"?>
<ruleset>
  <description>Example project ruleset</description>

  <!-- Include Alley Rules -->
  <rule ref="Alley-Interactive" />

  <!-- Project customizations go here -->
</ruleset>
```

## Testing

When contributing to this project, modifications to the ruleset should have a
corresponding test in the `tests` directory. For the most part, this takes the
form of a passing test in `tests/fixtures/pass` and a failing one in
`tests/fixtures/fail`. You can run the tests with `composer phpunit`. If you
want to run PHPCS against the test fixtures, you can run
`composer phpcs:fixtures` to ensure that what is passing/failing matches your
expectations. For failing fixtures in `tests/fixtures/fail`, we recommend
keeping the files smaller and focused on the specific sniff being tested.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

This project is actively maintained by [Alley Interactive](https://github.com/alleyinteractive).
Like what you see? [Come work with us](https://alley.com/careers/).

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.
