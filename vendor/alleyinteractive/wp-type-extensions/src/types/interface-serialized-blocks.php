<?php
/**
 * Serialized_Blocks interface file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Types;

/**
 * Describes an object with blocks that can be serialized.
 */
interface Serialized_Blocks {
	/**
	 * Serialized block content.
	 *
	 * @return string
	 */
	public function serialized_blocks(): string;
}
