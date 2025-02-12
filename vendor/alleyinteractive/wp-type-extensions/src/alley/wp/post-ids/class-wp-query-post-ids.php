<?php
/**
 * Query_Post_IDs class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Post_IDs;

use Alley\WP\Types\Post_IDs;
use WP_Post;
use WP_Query;

/**
 * The post IDs from a `WP_Query`.
 */
final class WP_Query_Post_IDs implements Post_IDs {
	/**
	 * Set up.
	 *
	 * @param WP_Query $query Query object.
	 */
	public function __construct(
		private readonly WP_Query $query,
	) {}

	/**
	 * Post IDs.
	 *
	 * @return int[]
	 */
	public function post_ids(): array {
		$ids = [];

		if ( \is_array( $this->query->posts ) ) {
			$ids = array_map( [ self::class, 'to_post_id' ], $this->query->posts );
		}

		return $ids;
	}

	/**
	 * Get the ID from a post object, ID, or ID-parent object.
	 *
	 * @param WP_Post|int|object $value Post-like object or ID.
	 * @return int Post ID.
	 */
	private static function to_post_id( $value ): int {
		$id = 0;

		if ( $value instanceof WP_Post ) {
			$id = $value->ID;
		}

		// fields => 'ids'.
		if ( is_numeric( $value ) ) {
			$id = $value;
		}

		// fields => 'id=>parent'.
		if ( ( $value instanceof \stdClass ) && isset( $value->ID ) ) {
			$id = $value->ID;
		}

		return (int) $id;
	}
}
