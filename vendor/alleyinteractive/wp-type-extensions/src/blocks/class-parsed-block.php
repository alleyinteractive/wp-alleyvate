<?php
/**
 * Parsed_Block class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Blocks;

use Alley\WP\Types\Single_Block;
use WP_Block_Parser_Block;

/**
 * A single parsed block.
 */
final class Parsed_Block implements Single_Block {
	/**
	 * Set up.
	 *
	 * @phpstan-param array<mixed> $origin
	 *
	 * @param array $origin Parsed block.
	 */
	public function __construct(
		private readonly array $origin,
	) {}

	/**
	 * Block name.
	 *
	 * @return string|null
	 */
	public function block_name(): ?string {
		return isset( $this->origin['blockName'] ) && \is_string( $this->origin['blockName'] ) ? $this->origin['blockName'] : null;
	}

	/**
	 * Parsed block.
	 *
	 * @phpstan-return array{blockName: ?string, attrs: array<string, mixed>, innerBlocks: array<mixed[]>, innerHTML: string, innerContent: string[]}
	 *
	 * @return array
	 */
	public function parsed_block(): array {
		$attrs         = isset( $this->origin['attrs'] ) && \is_array( $this->origin['attrs'] ) ? $this->origin['attrs'] : [];
		$inner_blocks  = isset( $this->origin['innerBlocks'] ) && \is_array( $this->origin['innerBlocks'] ) ? $this->origin['innerBlocks'] : [];
		$inner_html    = isset( $this->origin['innerHTML'] ) && \is_string( $this->origin['innerHTML'] ) ? $this->origin['innerHTML'] : '';
		$inner_content = isset( $this->origin['innerContent'] ) && \is_array( $this->origin['innerContent'] ) ? $this->origin['innerContent'] : [];

		return [
			'blockName'    => $this->block_name(),
			'attrs'        => $attrs,
			'innerBlocks'  => $inner_blocks,
			'innerHTML'    => $inner_html,
			'innerContent' => $inner_content,
		];
	}

	/**
	 * Serialized block content.
	 *
	 * @return string
	 */
	public function serialized_blocks(): string {
		return serialize_block( $this->parsed_block() ); // @phpstan-ignore-line argument.type
	}
}
