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
	 * @param mixed[] $origin Parsed block.
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
	 * @return mixed[]
	 */
	public function parsed_block(): array {
		$attrs         = isset( $this->origin['attrs'] ) && \is_array( $this->origin['attrs'] ) ? $this->origin['attrs'] : [];
		$inner_blocks  = isset( $this->origin['innerBlocks'] ) && \is_array( $this->origin['innerBlocks'] ) ? $this->origin['innerBlocks'] : [];
		$inner_html    = isset( $this->origin['innerHTML'] ) && \is_string( $this->origin['innerHTML'] ) ? $this->origin['innerHTML'] : '';
		$inner_content = isset( $this->origin['innerContent'] ) && \is_array( $this->origin['innerContent'] ) ? $this->origin['innerContent'] : [];

		return (array) new WP_Block_Parser_Block(
			$this->block_name(), // @phpstan-ignore-line
			$attrs,
			$inner_blocks,
			$inner_html,
			$inner_content,
		);
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
