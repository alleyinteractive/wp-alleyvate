<?php
/**
 * Process interface file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Types;

/**
 * A feature that can be stopped, not just started.
 */
interface Process extends Feature {
	/**
	 * Halt the feature.
	 */
	public function halt(): void;
}
