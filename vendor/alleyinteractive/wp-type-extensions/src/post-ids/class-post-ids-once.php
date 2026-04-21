<?php
/**
 * Post_IDs_Once class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Post_IDs;

use Alley\WP\Types\Post_IDs;

/**
 * Post IDs generated once.
 */
final class Post_IDs_Once implements Post_IDs {
	/**
	 * Set up.
	 *
	 * @param Post_IDs $origin Post IDs.
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
		return once( [ $this->origin, 'post_ids' ] );
	}
}
