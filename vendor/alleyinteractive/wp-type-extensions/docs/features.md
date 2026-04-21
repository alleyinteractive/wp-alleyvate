# Features interface

The `Features` interface describes multiple project features. This interface extends the `Feature` interface with an `include()` method for adding new features to an object. The primary use case for this interface is to compile a list of features for a plugin or theme before booting the final collection.

## Definition

```php
interface Features extends Feature{
	public function include( Feature ...$features ): void;
}
```

## Bundled implementations

- [Group](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/features/class-group.php): Group related features.

## Basic usage

```php
use Alley\WP\Features\Effect;
use Alley\WP\Features\Group;
use Alley\WP\Features\Lazy_Feature;
use Alley\WP\Features\Library;
use Alley\WP\Features\Ordered;
use Alley\WP\Features\Template_Feature;

// Main plugin features.
$plugin = new Group();

$plugin->include(
  new Features\Allowed_Blocks(
    context: 'core/edit-post',
    allowed: new FastFailValidatorChain(
      [
        new AnyValidator(
          [
            // Allow all blocks from WP Curate.
            new Regex( '/^wp-curate/' ),
            // Allow these specific other blocks.
            new OneOf(
              [
                'haystack' => [
                  'core/cover',
                  'core/embed',
                  'core/gallery',
                  'core/group',
                  'core/heading',
                  'core/image',
                  'core/list',
                  'core/list-item',
                  'core/paragraph',
                  'core/quote',
                ],
              ],
            ),
          ],
        ),
        new Not(
          new Regex( '#^core/post-#' ),
          'Post template blocks are not allowed in the post editor',
        ),
      ],
    ),
    registry: WP_Block_Type_Registry::get_instance(),
  ),
)

// Load the Simple History plugin.
$plugin->include(
  new Library\Plugin_Loader(
    plugins: [
      'simple-history/index.php',
    ],
  ),
);

// Load the Block Visibility plugin, then related features, except on the main site in the network.
$plugin->include(
  new Effect(
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
  ),
);

// Load the Google Tag Manager script on templates.
$plugin->include(
  new Template_Feature(
    origin: new Lazy_Feature(
      fn () => new Library\GTM_Script(
        gtm_id: 'GTM-XXXXXXX',
        data_layer: [
          'pageType' => is_page() ? 'page' : 'post',
          'pageCategory' => get_the_category(),
        ],
      ),
    ),
  ),
);

// Other one-off hooks.
$plugin->include(
  new Quick_Feature(
    function () {
      remove_action( /* ... */ );
      remove_filter( /* ... */ );
    },
  ),
);

$plugin->boot();
```
