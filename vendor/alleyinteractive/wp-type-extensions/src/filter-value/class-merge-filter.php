<?php
/**
 * Merge_Filter class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Filter_Value;

use Alley\WP\Types\Filter_Value;

/**
 * A filter that merges a value onto an array.
 */
final class Merge_Filter implements Filter_Value {
	/**
	 * Constructor.
	 *
	 * @param mixed[] $merge Value to merge.
	 */
	public function __construct(
		private readonly array $merge,
	) {}

	/**
	 * Called when a script tries to call an object as a function.
	 *
	 * @param mixed $value The value to filter.
	 * @return mixed
	 */
	public function __invoke( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			$value = array_merge( $value, $this->merge );
		}

		return $value;
	}
}
