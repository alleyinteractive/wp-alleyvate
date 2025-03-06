<?php
/**
 * Ordered class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features;

use Alley\WP\Types\Feature;

/**
 * Boot features in a guaranteed order.
 */
final class Ordered implements Feature {
	/**
	 * Constructor.
	 *
	 * @param Feature $first The first feature to boot.
	 * @param Feature $then  The feature to boot after.
	 */
	public function __construct(
		private readonly Feature $first,
		private readonly Feature $then,
	) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		$this->first->boot();
		$this->then->boot();
	}
}
