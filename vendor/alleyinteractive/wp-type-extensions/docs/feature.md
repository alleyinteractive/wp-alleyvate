# Feature interface

The `Feature` interface describes a project feature. Features can be large or small, although smaller features can take advantage of decorators more easily. Use the `boot()` method to add actions and filters.

## Definition

```php
interface Feature {
    public function boot(): void;
}
```

## Bundled implementations

- [By_Default](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-by-default.php): Boot a feature unless a condition is true.
- [Effect](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-effect.php): Boot a feature as an effect of a condition being true.
- [Lazy_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-lazy-feature.php): Instantiate a feature only when called upon.
- [Ordered](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-ordered.php): Boot features in a guaranteed order.
- [Quick_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-quick-feature.php): Make a callable a feature.
- [Template_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-template-feature.php): Boot a feature only when templates load.
- [Widget_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-widget-feature.php): Register a single widget.
- [Widget_Features](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-widget-features.php): Register multiple widgets.
- [WP_CLI_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-wp-cli-feature.php): Boot a feature only when WP-CLI loads.

All `Features` implementations also implement `Feature`.

## Basic usage

See the [documentation for the Features interface](./features.md) for a more comprehensive example.

```php
use Alley\WP\Features\Effect;
use Alley\WP\Features\Group;
use Alley\WP\Features\Lazy_Feature;
use Alley\WP\Features\Ordered;
use Alley\WP\Features\Quick_Feature;
use Alley\WP\Features\Template_Feature;

$feature = new Effect(
  when: fn () => get_current_blog_id() !== 1,
  then: new Ordered(
    first: new Quick_Feature(
      fn () => wpcom_vip_load_plugin( 'block-visibility/block-visibility.php' ),
    ),
    then: new Group(
      new Features\Block_Visibility_Settings(),
      new Features\Block_Visibility_Custom_Conditions(),
    ),
  ),
);
$feature->boot();
```
