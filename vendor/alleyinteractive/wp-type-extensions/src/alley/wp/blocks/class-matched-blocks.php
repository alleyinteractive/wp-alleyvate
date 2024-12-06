<?php
/**
 * Matched_Blocks class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Blocks;

use Alley\WP\Types\Serialized_Blocks;

use function Alley\WP\match_blocks;

/**
 * Blocks matched with {@see match_blocks()}.
 */
final class Matched_Blocks implements Serialized_Blocks {
	/**
	 * Set up.
	 *
	 * @param array<string, mixed> $args   Args for {@see match_blocks()}.
	 * @param Serialized_Blocks    $origin Blocks to search.
	 */
	public function __construct(
		private readonly array $args,
		private readonly Serialized_Blocks $origin,
	) {}

	/**
	 * Serialized block content.
	 *
	 * @return string
	 */
	public function serialized_blocks(): string {
		$matched = match_blocks( $this->origin->serialized_blocks(), $this->args );

		return \is_array( $matched ) ? serialize_blocks( $matched ) : '';
	}
}
