# Serialized Blocks interface

The `Serialized_Blocks` interface describes an object containing blocks that can be serialized to block markup. Any string can be serialized to block markup via the `Block_Content` class.

## Definition

```php
interface Serialized_Blocks {
	public function serialized_blocks(): string;
}
```

## Bundled implementations

- [Blocks](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/blocks/class-blocks.php): Bundle many blocks.
- [Each_Replaced_Blocks](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/blocks/class-each-replaced-blocks.php): Replace each matched block with other block content.
- [Matched_Blocks](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/blocks/class-matched-blocks.php): Blocks matched with `match_blocks()`.
- [Block_Content](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/blocks/class-block-content.php): Blocks in the given content.
- [Lazy_Blocks](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/blocks/class-lazy-blocks.php): Instantiate blocks only when called upon.

All `Single_Block` implementations also implement `Serialized_Blocks`.

