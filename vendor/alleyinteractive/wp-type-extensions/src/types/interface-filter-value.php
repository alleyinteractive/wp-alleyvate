<?php
/**
 * Filter_Value interface file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Types;

/**
 * Represents a filter behavior.
 */
interface Filter_Value {
	/**
	 * Called when a script tries to call an object as a function.
	 *
	 * @param mixed $value The value to filter.
	 * @return mixed
	 */
	public function __invoke( mixed $value ): mixed;
}
