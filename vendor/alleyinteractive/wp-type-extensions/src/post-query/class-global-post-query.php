<?php
/**
 * Global_Post_Query class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Post_Query;

use Alley\WP\Types\Post_Query;
use WP_Post;
use WP_Query;

/**
 * Post_Query implementation for a query in $GLOBALS.
 */
final class Global_Post_Query implements Post_Query {
	/**
	 * Set up.
	 *
	 * @param string   $global  Global variable name.
	 * @param WP_Query $default Default query object.
	 */
	public function __construct(
		private readonly string $global,
		private readonly WP_Query $default = new WP_Query(),
	) {}

	/**
	 * Query object.
	 *
	 * @return WP_Query
	 */
	public function query_object(): WP_Query {
		return isset( $GLOBALS[ $this->global ] ) && $GLOBALS[ $this->global ] instanceof WP_Query
			? $GLOBALS[ $this->global ]
			: $this->default;
	}

	/**
	 * Found post objects.
	 *
	 * @return WP_Post[]
	 */
	public function post_objects(): array {
		$query = new WP_Query_Envelope( $this->query_object() );

		return $query->post_objects();
	}

	/**
	 * Found post IDs.
	 *
	 * @return int[]
	 */
	public function post_ids(): array {
		$query = new WP_Query_Envelope( $this->query_object() );

		return $query->post_ids();
	}
}
