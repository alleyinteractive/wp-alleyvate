<?php
/**
 * Legal_Object_IDs class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP;

use Alley\WP\Types\Post_IDs;

/**
 * Only legal database IDs for a WordPress object.
 */
final class Legal_Object_IDs implements Post_IDs {
	/**
	 * Set up.
	 *
	 * @param Post_IDs $origin Original IDs.
	 */
	public function __construct(
		private readonly Post_IDs $origin,
	) {}

	/**
	 * Post IDs.
	 *
	 * @return int[]
	 */
	public function post_ids(): array {
		return array_filter( $this->origin->post_ids(), fn ( $id ) => $id > 0 );
	}
}
