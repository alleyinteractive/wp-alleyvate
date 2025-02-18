<?php
/**
 * Features interface file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Types;

/**
 * Describes multiple features.
 */
interface Features extends Feature {
	/**
	 * Include features.
	 *
	 * @param Feature ...$features Features to include.
	 */
	public function include( Feature ...$features ): void;
}
