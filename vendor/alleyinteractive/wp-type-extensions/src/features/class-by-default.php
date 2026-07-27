<?php
/**
 * By_Default class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features;

use Alley\WP\Types\Feature;

/**
 * Boot a feature unless a condition is true.
 */
final class By_Default implements Feature {
	/**
	 * The condition to check.
	 *
	 * @var callable
	 */
	private $unless;

	/**
	 * Constructor.
	 *
	 * @param Feature  $use    The feature to boot unless the condition is met.
	 * @param callable $unless The condition to check.
	 */
	public function __construct(
		private readonly Feature $use,
		callable $unless,
	) {
		$this->unless = $unless;
	}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		if ( ( $this->unless )() === false ) {
			$this->use->boot();
		}
	}
}
