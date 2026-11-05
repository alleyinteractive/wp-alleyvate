<?php
/**
 * Group class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features;

use Alley\WP\Types\Feature;
use Alley\WP\Types\Features;

/**
 * Group many features.
 */
final class Group implements Features {
	/**
	 * Collected features.
	 *
	 * @var Feature[]
	 */
	private array $features;

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

	/**
	 * Include a feature.
	 *
	 * @param Feature $feature Feature to include.
	 */
	public function include( Feature $feature ): void {
		$this->features[] = $feature;
	}
}
