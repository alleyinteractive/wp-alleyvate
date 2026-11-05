<?php
/**
 * Lazy_Feature class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features;

use Alley\WP\Types\Feature;

/**
 * Instantiate a feature only when called upon.
 */
final class Lazy_Feature implements Feature {
	/**
	 * Set up.
	 *
	 * @param callable(): Feature $final Callback to create the feature.
	 */
	public function __construct(
		private $final,
	) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		( $this->final )()->boot();
	}
}
