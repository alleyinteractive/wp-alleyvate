# Single Block interface

The `Single_Block` interface describes an object containing a single block.

## Definition

```php
interface Single_Block {
	public function block_name(): ?string;

	public function parsed_block(): array;
}
```

## Bundled implementations

- [Named_Block](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/blocks/class-named-block.php): A single block with the given block name.
- [Parsed_Block](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/blocks/class-parsed-block.php): A single parsed block.
- [Default_Classname_Block](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/blocks/class-default-classname-block.php): Block wrapped in the block default classname.
- [Inner_Blocks_Prepended](https://github.com/alleyinteractive/wp-type-extensions/blob/main/src/alley/wp/blocks/class-inner-blocks-prepended.php): Block with prepended inner blocks.
