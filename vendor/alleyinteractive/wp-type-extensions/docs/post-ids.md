# Post IDs interface

The `Post_IDs` interface describes an object containing post IDs, such as the IDs in a query or a curated set of featured posts.

## Definition

```php
interface Post_IDs {
	public function post_ids(): array;
}
```

## Bundled implementations

- [Empty_Post_IDs](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/post-ids/class-empty-post-ids.php): No post IDs.
- [Memoized_Post_IDs](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/post-ids/class-memoized-post-ids.php): Always returns the same set of IDs from the original instance.
- [Post_IDs_Envelope](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/post-ids/class-post-ids-envelope.php): Instance from an existing set of IDs.
- [WP_Query_Post_IDs](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/post-ids/class-wp-query-post-ids.php): The post IDs from a `WP_Query`.

All `Post_Query` implementations also implement `Post_IDs`.
