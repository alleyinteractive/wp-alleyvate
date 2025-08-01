<?php
/**
 * Lazy_Blocks class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Blocks;

use Alley\WP\Types\Serialized_Blocks;

/**
 * Instantiate blocks only when called upon.
 */
final class Lazy_Blocks implements Serialized_Blocks {
	/**
	 * Set up.
	 *
	 * @param callable(): Serialized_Blocks $final Callback to create the blocks.
	 */
	public function __construct(
		private $final,
	) {}

	/**
	 * Serialized block content.
	 *
	 * @return string
	 */
	public function serialized_blocks(): string {
		return ( $this->final )()->serialized_blocks();
	}
}
