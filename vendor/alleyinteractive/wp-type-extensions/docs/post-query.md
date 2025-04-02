# Post Query interface

The `Post_Query` interface describes an object that contains a single query for posts.

## Definition

```php
interface Post_Query extends Post_IDs {
	public function query_object(): WP_Query;

	public function post_objects(): array;
}
```

## Bundled implementations

- [Global_Post_Query](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-query/class-global-post-query.php): Post_Query for a query in `$GLOBALS`.
- [Post_IDs_Query](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-query/class-post-ids-query.php): Query from post IDs.
- [WP_Query_Envelope](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-query/class-wp-query-envelope.php): Post_Query from an existing query.
