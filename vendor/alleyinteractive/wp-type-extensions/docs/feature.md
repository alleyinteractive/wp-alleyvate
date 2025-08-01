# Feature interface

The `Feature` interface describes a project feature. Features can be large or small, although smaller features can take advantage of decorators more easily. Use the `boot()` method to add actions and filters.

## Definition

```php
interface Feature {
    public function boot(): void;
}
```

## Bundled implementations

- [Effect](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-effect.php): Boot a feature as an effect of a condition being true.
- [Lazy_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-lazy-feature.php): Instantiate a feature only when called upon.
- [Ordered](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-ordered.php): Boot features in a guaranteed order.
- [Quick_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-quick-feature.php): Make a callable a feature.
- [Template_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-template-feature.php): Boot a feature only when templates load.
- [WP_CLI_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-wp-cli-feature.php): Boot a feature only WP-CLI loads.

All `Features` implementations also implement `Feature`.

## Feature library

The `Library` subnamespace includes concrete implementations of common features. These can be used on their own or as part of a set of features that make up a larger integration.

- [Allowed_Blocks](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/library/class-allowed-blocks.php): Limit blocks allowed in the editor to those that are explicitly supported.
- [Block_Content_Filter](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/library/class-block-content-filter.php): Filter block markup in `the_content` for the post being viewed.
- [GTM_Script](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/library/class-gtm-script.php): Add the standard Google Tag Manager script and data layer.
- [Plugin_Loader](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/library/class-plugin-loader.php): Makes the [Alley plugin loader](https://github.com/alleyinteractive/wp-plugin-loader) available in a feature.

## Basic usage

See the [documentation for the Features interface](./features.md) for a more comprehensive example.

```php
use Alley\WP\Features\Effect;
use Alley\WP\Features\Group;
use Alley\WP\Features\Lazy_Feature;
use Alley\WP\Features\Library;
use Alley\WP\Features\Ordered;
use Alley\WP\Features\Template_Feature;

$feature = new Effect(
  when: fn () => get_current_blog_id() !== 1,
  then: new Ordered(
    first: new Library\Plugin_Loader(
      plugins: [
        'block-visibility/block-visibility.php',
      ],
    ),
    then: new Group(
      new Features\Block_Visibility_Settings(),
      new Features\Block_Visibility_Custom_Conditions(),
    ),
  ),
);
$feature->boot();
```
