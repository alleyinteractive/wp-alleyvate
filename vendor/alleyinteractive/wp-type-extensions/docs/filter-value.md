# Filter Value interface

The `Filter_Value` interface describes an object that filters a value. Implementations are invokable objects, which means they can be passed directly to `add_filter()`.

## Definition

```php
interface Filter_Value {
	public function __invoke( mixed $value ): mixed;
}
```

## Bundled implementations

- [Merge_Filter](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/filter-value/class-merge-filter.php): Merge an array onto the filtered value.
- [Push_Filter](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/filter-value/class-push-filter.php): Push a single item onto the filtered value.

## Basic usage

```php
use Alley\WP\Filter_Value\Merge_Filter;
use Alley\WP\Filter_Value\Push_Filter;

// Merge additional arguments into a query args array.
add_filter(
	'my_query_args',
	new Merge_Filter( [ 'post_status' => 'publish', 'posts_per_page' => 10 ] ),
);

// Push a post type onto an allowed post types filter.
add_filter(
	'my_allowed_post_types',
	new Push_Filter( 'page' ),
);
```
