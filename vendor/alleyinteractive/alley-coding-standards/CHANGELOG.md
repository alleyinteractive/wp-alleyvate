# Changelog

This project adheres to [Keep a CHANGELOG](https://keepachangelog.com/en/1.0.0/).

This project adheres to [Keep a CHANGELOG](https://keepachangelog.com/en/1.0.0/).

### 2.1.0

- Allow PSR-4 style `ClassName.php` file names to support our migration to PSR-4 for test files.
- Remove the `PEAR.Functions.FunctionCallSignature` sniff from the ruleset and
  replace it with `PSR2.Methods.FunctionCallSignature`. This change is to allow
  for multi-line function calls to be formatted in a more readable way without having to insert a new line before the first argument.
- Allow camelCase'd DOMDocument/DOMElement/etc. property names to not be flagged by `WordPress.NamingConventions.ValidVariableName`.

### 2.0.2

- Fix issue with files with `js`/`css` in the path being ignored.
- Bumping to PHP 8.1.

### 2.0.1

- Update "Prefer array syntax" rule to 3.0.

### 2.0.0

- **Breaking Change:** Upgraded to `automattic/vipwpcs` and
  `wp-coding-standards/wpcs` to 3.0. See [Upgrading to 2.0](https://github.com/alleyinteractive/alley-coding-standards/wiki/Upgrading-to-2.0)
  for more details.

### 1.0.1

- Ignore deprecation errors in WPCS to allow it work with PHP 8.0+.

### 1.0.0

- No changes, tagging a stable release of Alley Coding Standards.

### 0.4.1

- Upgrading to `automattic/vipwpcs` v2.3.3 and `dealerdirect/phpcodesniffer-composer-installer` v0.7.2.

### 0.4.0

- Add PHPCompatibilityWP sniffs to our rules, configured for PHP 8.0+
- Make template-parts rule checking more ambiguous to better support scanning standalone plugins and themes
- Added `static analysis` keyword to Composer to promote package to be installed with `--dev`.

### 0.3.0

- Add PHPCompatibilityWP standard as a dependency (#9)
- Exclude plugin template parts from WordPress.NamingConventions.PrefixAllGlobals sniff (#11)
- Remove 'wp_cache_set' from forbidden functions (#12)

### 0.2.0

- Sniff name changed to Alley-Interactive.
- Composer package renamed to `alleyinteractive/alley-coding-standards`.
- Allow short ternary syntax (#6)

### 0.1.0

- Initial release.
