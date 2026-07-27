<?php
/**
 * Quick_Feature class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features;

use Alley\WP\Types\Feature;

/**
 * Make a callable a feature.
 */
final class Quick_Feature implements Feature {
	/**
	 * Set up.
	 *
	 * @param callable $fn Function.
	 */
	public function __construct(
		private $fn,
	) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		( $this->fn )();
	}
}
