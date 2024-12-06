<?php
/**
 * Named_Block class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Blocks;

use Alley\WP\Types\Single_Block;

/**
 * A single block with the given block name.
 */
final class Named_Block implements Single_Block {
	/**
	 * Set up.
	 *
	 * @param string               $block_name Block name.
	 * @param array<string, mixed> $attrs      Block attributes.
	 * @param string               $inner_html Block inner HTML.
	 */
	public function __construct(
		private readonly string $block_name,
		private readonly array $attrs = [],
		private readonly string $inner_html = '',
	) {}

	/**
	 * Block name.
	 *
	 * @return string
	 */
	public function block_name(): string {
		return $this->block_name;
	}

	/**
	 * Parsed block.
	 *
	 * @return mixed[]
	 */
	public function parsed_block(): array {
		return [
			'blockName'    => $this->block_name,
			'attrs'        => $this->attrs,
			'innerBlocks'  => [],
			'innerHTML'    => $this->inner_html,
			'innerContent' => [ $this->inner_html ],
		];
	}

	/**
	 * Serialized block content.
	 *
	 * @return string
	 */
	public function serialized_blocks(): string {
		return serialize_block( $this->parsed_block() );
	}
}
