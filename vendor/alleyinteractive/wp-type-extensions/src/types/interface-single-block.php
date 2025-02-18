<?php
/**
 * Single_Block interface file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Types;

/**
 * Describes a single block.
 */
interface Single_Block extends Serialized_Blocks {
	/**
	 * Block name.
	 *
	 * @return string|null
	 */
	public function block_name(): ?string;

	/**
	 * Parsed block.
	 *
	 * @phpstan-return array{blockName: ?string, attrs: array<string, mixed>, innerBlocks: array<mixed[]>, innerHTML: string, innerContent: string[]}
	 *
	 * @return array
	 */
	public function parsed_block(): array;
}
