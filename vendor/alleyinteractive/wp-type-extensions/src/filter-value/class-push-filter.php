<?php
/**
 * Push_Filter class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Filter_Value;

use Alley\WP\Types\Filter_Value;

/**
 * A filter that pushes a value onto an array.
 */
final class Push_Filter implements Filter_Value {
	/**
	 * Constructor.
	 *
	 * @param mixed $push Value to push.
	 */
	public function __construct(
		private readonly mixed $push,
	) {}

	/**
	 * Called when a script tries to call an object as a function.
	 *
	 * @param mixed $value The value to filter.
	 * @return mixed
	 */
	public function __invoke( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			$value[] = $this->push;
		}

		return $value;
	}
}
