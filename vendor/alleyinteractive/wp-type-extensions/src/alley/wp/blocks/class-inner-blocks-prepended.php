<?php
/**
 * Inner_Blocks_Prepended class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Blocks;

use Alley\WP\Types\Serialized_Blocks;
use Alley\WP\Types\Single_Block;

/**
 * Block with prepended inner blocks.
 */
final class Inner_Blocks_Prepended implements Single_Block {
	/**
	 * Set up.
	 *
	 * @param Serialized_Blocks $block  Inner block.
	 * @param Single_Block      $target Target block.
	 */
	public function __construct(
		private readonly Serialized_Blocks $block,
		private readonly Single_Block $target,
	) {}

	/**
	 * Block name.
	 *
	 * @return string|null
	 */
	public function block_name(): ?string {
		return $this->target->block_name();
	}

	/**
	 * Parsed block.
	 *
	 * @return mixed[]
	 */
	public function parsed_block(): array {
		$out = $this->target->parsed_block();
		$add = parse_blocks( $this->block->serialized_blocks() );

		if (
			\is_array( $add )
			&& isset( $out['innerBlocks'], $out['innerContent'] )
			&& \is_array( $out['innerBlocks'] )
			&& \is_array( $out['innerContent'] )
		) {
			$out['innerBlocks']  = array_merge( $add, $out['innerBlocks'] );
			$out['innerContent'] = array_merge(
				array_fill( 0, \count( $add ), null ),
				$out['innerContent'],
			);
		}

		return $out;
	}

	/**
	 * Serialized block content.
	 *
	 * @return string
	 */
	public function serialized_blocks(): string {
		$pb = new Parsed_Block( $this->parsed_block() );
		return $pb->serialized_blocks();
	}
}
