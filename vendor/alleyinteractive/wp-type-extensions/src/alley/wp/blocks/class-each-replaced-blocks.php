<?php
/**
 * Each_Replaced_Blocks class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Blocks;

use Alley\WP\Types\Serialized_Blocks;

/**
 * Replace each matched block with other block content.
 */
final class Each_Replaced_Blocks implements Serialized_Blocks {
	/**
	 * Set up.
	 *
	 * @param Serialized_Blocks $find    Blocks to find.
	 * @param Serialized_Blocks $replace Replacement for each found block.
	 * @param Serialized_Blocks $origin  Blocks to search.
	 */
	public function __construct(
		private readonly Serialized_Blocks $find,
		private readonly Serialized_Blocks $replace,
		private readonly Serialized_Blocks $origin,
	) {}

	/**
	 * Serialized block content.
	 *
	 * @return string
	 */
	public function serialized_blocks(): string {
		$out = $this->origin->serialized_blocks();

		$needles = parse_blocks( $this->find->serialized_blocks() );

		if ( \is_array( $needles ) && count( $needles ) > 0 ) {
			foreach ( $needles as $needle ) {
				if ( \is_array( $needle ) ) {
					$pbn = new Parsed_Block( $needle );
					$out = str_replace( $pbn->serialized_blocks(), $this->replace->serialized_blocks(), $out );
				}
			}
		}

		return $out;
	}
}
