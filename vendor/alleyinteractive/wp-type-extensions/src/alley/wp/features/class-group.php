<?php
/**
 * Group class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features;

use Alley\WP\Types\Feature;

/**
 * Group many features.
 */
final class Group implements Feature {
	/**
	 * Collected features.
	 *
	 * @var Feature[]
	 */
	private readonly array $features;

	/**
	 * Set up.
	 *
	 * @param Feature ...$features Features.
	 */
	public function __construct( Feature ...$features ) {
		$this->features = $features;
	}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		foreach ( $this->features as $feature ) {
			$feature->boot();
		}
	}
}
