<?php
/**
 * Default_Post_Queries class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Post_Queries;

use Alley\WP\Post_Query\WP_Query_Envelope;
use Alley\WP\Types\Post_Queries;
use Alley\WP\Types\Post_Query;
use WP_Query;

/**
 * Queries implementation for most cases.
 */
final class Default_Post_Queries implements Post_Queries {
	/**
	 * Query for posts using literal arguments.
	 *
	 * @param array<string, mixed> $args The arguments to be used in the query.
	 * @return Post_Query
	 */
	public function query( array $args ): Post_Query {
		return new WP_Query_Envelope( new WP_Query( $args ) );
	}
}
