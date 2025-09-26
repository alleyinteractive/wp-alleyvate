<?php
/**
 * Each_Appended class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Blocks;

use Alley\WP\Types\Serialized_Blocks;

/**
 * Append each matched block with other block content.
 */
final class Each_Appended implements Serialized_Blocks {
	/**
	 * Set up.
	 *
	 * @param Serialized_Blocks $targets Blocks to append to.
	 * @param Serialized_Blocks $append  Append to each found block.
	 * @param Serialized_Blocks $origin  Blocks to amend.
	 */
	public function __construct(
		private readonly Serialized_Blocks $targets,
		private readonly Serialized_Blocks $append,
		private readonly Serialized_Blocks $origin,
	) {}

	/**
	 * Serialized block content.
	 *
	 * @return string
	 */
	public function serialized_blocks(): string {
		$out = $this->origin->serialized_blocks();

		$target_blocks = parse_blocks( $this->targets->serialized_blocks() );

		if ( \is_array( $target_blocks ) && count( $target_blocks ) > 0 ) {
			foreach ( $target_blocks as $target_block ) {
				$find    = new Parsed_Block( $target_block );
				$replace = new Blocks( $find, $this->append );
				$out     = str_replace( $find->serialized_blocks(), $replace->serialized_blocks(), $out );
			}
		}

		return $out;
	}
}
