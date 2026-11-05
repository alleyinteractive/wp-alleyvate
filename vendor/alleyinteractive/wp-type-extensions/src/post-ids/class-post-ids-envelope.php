<?php
/**
 * Post_IDs_Envelope class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Post_IDs;

use Alley\WP\Types\Post_IDs;

/**
 * Post_IDs from an existing set of IDs.
 */
final class Post_IDs_Envelope implements Post_IDs {
	/**
	 * Set up.
	 *
	 * @param int[] $post_ids Post IDs.
	 */
	public function __construct(
		private readonly array $post_ids,
	) {}

	/**
	 * Post IDs.
	 *
	 * @return int[]
	 */
	public function post_ids(): array {
		return array_map( 'intval', $this->post_ids );
	}
}
