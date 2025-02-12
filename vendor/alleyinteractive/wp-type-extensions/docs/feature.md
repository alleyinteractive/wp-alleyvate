# Feature interface

The `Feature` interface describes a project feature. Features can be large or small, although smaller features can take advantage of decorators more easily. Use the `boot()` method to add actions and filters. Group related features with the `Features` class.

## Definition

```php
interface Feature {
    public function boot(): void;
}
```

## Bundled implementations

- [Conditional_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/features/class-conditional-feature.php): Boot a feature only when a condition is met.
- [Group](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/features/class-group.php): Group related features.
- [GTM_Script](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/features/class-gtm-script.php): Add the standard Google Tag Manager script and data layer.
- [Lazy_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/features/class-lazy-feature.php): Instantiate a feature only when called upon.
- [Quick_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/features/class-quick-feature.php): Make a callable a feature.
- [Template_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/features/class-template-feature.php): Boot a feature only when templates load.
- [WP_CLI_Feature](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/features/class-wp-cli-feature.php): Boot a feature only WP-CLI loads.

## Basic usage

```php
use Alley\WP\Features\Group;
use Alley\WP\Features\Quick_Feature;
use Alley\WP\Features\Template_Feature;

$queries = new Project\Post_Queries_Implementation(
	/* ... */
);

$project = new Group(
	new Group(
		new Project\Ads_Backend_Feature(),
		new Template_Feature(
			origin: new Project\Ads_Frontend_Feature(),
		),
	),
	new Project\Other_Feature(
		queries: $queries,
	),
	new Quick_Feature(
		function () {
			remove_action( /* ... */ );
			remove_filter( /* ... */ );
		},
	)
);

$project->boot();
```
