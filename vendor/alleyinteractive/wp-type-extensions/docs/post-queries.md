# Post Queries interface

The `Post_Queries` interface describes an object that contains queries for posts.

## Definition

```php
interface Post_Queries {
	public function query( array $args ): Post_Query;
}
```

## Bundled implementations

- [Default_Post_Queries](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-queries/class-default-post-queries.php): Queries implementation for most cases.  
- [Enforced_Date_Queries](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-queries/class-enforced-date-queries.php): Queries that enforce a date query.
- [Exclude_Queries](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-queries/class-exclude-queries.php): Queries that exclude some posts.
- [Memoized_Post_Queries](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-queries/class-memoized-post-queries.php): Reuse queries given the same arguments.
- [Optimistic_Date_Queries](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-queries/class-optimistic-date-queries.php): Speculate (but don't require) that queries can be limited to posts published after the given dates.
- [Variable_Post_Queries](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/post-queries/class-variable-post-queries.php): Choose queries based on the result of a validation test.
