<?php
/**
 * Conditional_Feature class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features;

use Alley\WP\Types\Feature;
use Laminas\Validator\ValidatorInterface;

/**
 * Boot a feature only when a condition is met.
 */
final class Conditional_Feature implements Feature {
	/**
	 * Set up.
	 *
	 * @param ValidatorInterface $test    Validator to test the value.
	 * @param callable|mixed     $value   Value to test or callable that returns the value.
	 * @param Feature            $if_true Feature to boot if the test passes.
	 */
	public function __construct(
		private readonly ValidatorInterface $test,
		private $value,
		private readonly Feature $if_true,
	) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		$value = is_callable( $this->value ) ? ( $this->value )() : $this->value;

		if ( $this->test->isValid( $value ) ) {
			$this->if_true->boot();
		}
	}
}
