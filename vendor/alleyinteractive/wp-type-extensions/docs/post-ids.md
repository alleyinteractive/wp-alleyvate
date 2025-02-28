# Post IDs interface

The `Post_IDs` interface describes an object containing post IDs, such as the IDs in a query or a curated set of featured posts.

## Definition

```php
interface Post_IDs {
	public function post_ids(): array;
}
```

## Bundled implementations

- [Empty_Post_IDs](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-ids/class-empty-post-ids.php): No post IDs.
- [Post_IDs_Envelope](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-ids/class-post-ids-envelope.php): Instance from an existing set of IDs.
- [Post_IDs_Once](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-ids/class-post-ids-once.php): Always returns the same set of IDs from the original instance.
- [Used_Post_IDs](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-ids/class-used-post-ids.php): Track post IDs that have been used, e.g. while rendering a page.
- [WP_Query_Post_IDs](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-ids/class-wp-query-post-ids.php): The post IDs from a `WP_Query`.

All `Post_Query` implementations also implement `Post_IDs`.
