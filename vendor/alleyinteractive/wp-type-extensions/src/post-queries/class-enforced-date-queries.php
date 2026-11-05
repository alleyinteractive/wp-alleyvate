<?php
/**
 * Enforced_Date_Queries class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Post_Queries;

use Alley\WP\Types\Post_Queries;
use Alley\WP\Types\Post_Query;
use DateTimeInterface;

/**
 * Queries that enforce a date query.
 */
final class Enforced_Date_Queries implements Post_Queries {
	/**
	 * Set up.
	 *
	 * @param DateTimeInterface $after  Date to limit queries to.
	 * @param Post_Queries      $origin Post_Queries object.
	 */
	public function __construct(
		private readonly DateTimeInterface $after,
		private readonly Post_Queries $origin,
	) {}

	/**
	 * Query for posts using literal arguments.
	 *
	 * @param array<string, mixed> $args The arguments to be used in the query.
	 * @return Post_Query
	 */
	public function query( array $args ): Post_Query {
		$with_date_query = $this->with_date_query( $args, $this->after );

		return $this->origin->query( $with_date_query );
	}

	/**
	 * Add 'after' date query with the given date.
	 *
	 * @param array<string, mixed> $args  Query arguments.
	 * @param DateTimeInterface    $after Date instance.
	 * @return array<string, mixed>
	 */
	private function with_date_query( array $args, DateTimeInterface $after ): array {
		if ( ! isset( $args['date_query'] ) || ! \is_array( $args['date_query'] ) ) {
			$args['date_query'] = [];
		}

		$args['date_query']['relation'] = 'AND';
		$args['date_query'][]           = [
			'after' => [
				'year'  => $after->format( 'Y' ),
				'month' => $after->format( 'n' ),
				'day'   => $after->format( 'j' ),
			],
		];

		return $args;
	}
}
