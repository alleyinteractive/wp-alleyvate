<?php
/**
 * Feature interface file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Types;

/**
 * Describes a project feature.
 */
interface Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void;
}
