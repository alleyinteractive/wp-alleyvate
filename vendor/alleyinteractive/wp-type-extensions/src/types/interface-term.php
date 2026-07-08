<?php
/**
 * Term interface file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Types;

/**
 * Describes a single term.
 */
interface Term {
	/**
	 * Term ID.
	 *
	 * @return int
	 */
	public function term_id(): int;
}
