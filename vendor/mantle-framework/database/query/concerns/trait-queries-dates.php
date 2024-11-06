<?php
/**
 * Queries_Dates trait file
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Database\Query\Concerns;

use Carbon\Carbon;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Logic to query posts by dates.
 *
 * @link https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
 *
 * @todo Add support for more complex date queries (mixing AND/OR, etc.).
 *
 * @mixin \Mantle\Database\Query\Post_Query_Builder
 */
trait Queries_Dates {
	/**
	 * Date constraints to apply to the query.
	 *
	 * @var array<int, array{date: DateTimeInterface|int|string, compare: string, column: string}>
	 */
	protected array $date_constraints = [];

	/**
	 * The valid comparison operators for a date query.
	 */
	protected array $date_operators = [
		'=',
		'!=',
		'>',
		'>=',
		'<',
		'<=',
	];

	/**
	 * Add a date query for a date to the query.
	 *
	 * Defaults to comparing against the post published date.
	 *
	 * @throws InvalidArgumentException If an invalid comparison operator is provided.
	 *
	 * @param DateTimeInterface|int|string $date
	 * @param string                       $compare Comparison operator, defaults to '='.
	 * @param string                       $column Column to compare against, defaults to 'post_date'.
	 */
	public function whereDate( DateTimeInterface|int|string $date, string $compare = '=', string $column = 'post_date' ): static { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! in_array( $compare, $this->date_operators, true ) ) {
			throw new InvalidArgumentException( esc_html( 'Invalid date comparison operator: ' . $compare ) );
		}

		$this->date_constraints[] = compact( 'date', 'compare', 'column' );

		return $this;
	}

	/**
	 * Add a date query for the UTC publish date to the query.
	 *
	 * @param DateTimeInterface|int|string $date Date to compare against.
	 * @param string                       $compare Comparison operator, defaults to '='.
	 */
	public function whereUtcDate( DateTimeInterface|int|string $date, string $compare = '=' ): static {
		return $this->whereDate( $date, $compare, 'post_date_gmt' );
	}

	/**
	 * Add a date query for the modified date to the query.
	 *
	 * @param DateTimeInterface|int|string $date Date to compare against.
	 * @param string                       $compare Comparison operator, defaults to '='.
	 */
	public function whereModifiedDate( DateTimeInterface|int|string $date, string $compare = '=' ): static {
		return $this->whereDate( $date, $compare, 'post_modified' );
	}

	/**
	 * Add a date query for the modified UTC date to the query.
	 *
	 * @param DateTimeInterface|int|string $date Date to compare against.
	 * @param string                       $compare Comparison operator, defaults to '='.
	 */
	public function whereModifiedUtcDate( DateTimeInterface|int|string $date, string $compare = '=' ): static {
		return $this->whereDate( $date, $compare, 'post_modified_gmt' );
	}

	/**
	 * Query for objects older than the given date.
	 *
	 * @param DateTimeInterface|int $date Date to compare against.
	 * @param string                $column Column to compare against.
	 */
	public function olderThan( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->whereDate( $date, '<', $column );
	}

	/**
	 * Query for objects older than or equal to the given date.
	 *
	 * @param DateTimeInterface|int $date Date to compare against.
	 * @param string                $column Column to compare against.
	 */
	public function olderThanOrEqualTo( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->whereDate( $date, '<=', $column );
	}

	/**
	 * Query for objects older than or equal to now.
	 *
	 * @param string $column Column to compare against.
	 */
	public function olderThanNow( string $column = 'post_date' ): static {
		return $this->olderThanOrEqualTo( now(), $column );
	}

	/**
	 * Alias for olderThan().
	 *
	 * @param DateTimeInterface|int $date Date to compare against.
	 * @param string                $column Column to compare against.
	 */
	public function older_than( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->olderThan( $date, $column );
	}

	/**
	 * Alias for olderThanOrEqualTo().
	 *
	 * @param DateTimeInterface|int $date Date to compare against.
	 * @param string                $column Column to compare against.
	 */
	public function older_than_or_equal_to( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->whereDate( $date, '<=', $column );
	}

	/**
	 * Query for objects newer than the given date.
	 *
	 * @param DateTimeInterface|int $date
	 * @param string                $column Column to compare against.
	 */
	public function newerThan( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->whereDate( $date, '>', $column );
	}

	/**
	 * Query for objects newer than now (in the future from now).
	 *
	 * @param string $column Column to compare against.
	 */
	public function newerThanNow( string $column = 'post_date' ): static {
		return $this->newerThan( now(), $column );
	}

	/**
	 * Query for objects newer than or equal to the given date.
	 *
	 * @param DateTimeInterface|int $date
	 * @param string                $column Column to compare against.
	 */
	public function newerThanOrEqualTo( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->whereDate( $date, '>=', $column );
	}

	/**
	 * Alias for newerThan().
	 *
	 * @param DateTimeInterface|int $date Date to compare against.
	 * @param string                $column Column to compare against.
	 */
	public function newer_than( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->newerThan( $date, $column );
	}

	/**
	 * Alias for newerThanOrEqualTo().
	 *
	 * @param DateTimeInterface|int $date Date to compare against.
	 * @param string                $column Column to compare against.
	 */
	public function newer_than_or_equal_to( DateTimeInterface|int $date, string $column = 'post_date' ): static {
		return $this->newerThanOrEqualTo( $date, $column );
	}

	/**
	 * Calculate the arguments for the date query to pass to either WP_Query.
	 */
	protected function get_date_query_args(): array {
		if ( empty( $this->date_constraints ) ) {
			return [];
		}

		$date_query = [];

		foreach ( $this->date_constraints as $date_constraint ) {
			$date = $date_constraint['date'];

			if ( is_int( $date ) ) {
				$date = Carbon::createFromTimestamp( $date, wp_timezone() );
			} elseif ( is_string( $date ) ) {
				$date = Carbon::parse( $date, wp_timezone() );
			} elseif ( $date instanceof DateTimeInterface ) {
				$date = Carbon::instance( $date );
			}

			switch ( $date_constraint['compare'] ) {
				case '<':
					$date_query[] = [
						'column' => $date_constraint['column'],
						'before' => $date->toDateTimeString(),
					];
					break;

				case '<=':
					$date_query[] = [
						'column'    => $date_constraint['column'],
						'before'    => $date->toDateTimeString(),
						'inclusive' => true,
					];
					break;

				case '>':
					$date_query[] = [
						'column' => $date_constraint['column'],
						'after'  => $date->toDateTimeString(),
					];
					break;

				case '>=':
					$date_query[] = [
						'column'    => $date_constraint['column'],
						'after'     => $date->toDateTimeString(),
						'inclusive' => true,
					];
					break;

				// TODO: Review if a query for a specific date can be improved.
				case '=':
					$date_query[] = [
						'relation' => 'and',
						[
							'column'    => $date_constraint['column'],
							'before'    => $date->toDateTimeString(),
							'inclusive' => true,
						],
						[
							'column'    => $date_constraint['column'],
							'after'     => $date->toDateTimeString(),
							'inclusive' => true,
						],
					];
					break;

				case '!=':
					$date_query[] = [
						'relation' => 'or',
						[
							'column'    => $date_constraint['column'],
							'before'    => $date->toDateTimeString(),
							'inclusive' => false,
						],
						[
							'column'    => $date_constraint['column'],
							'after'     => $date->toDateTimeString(),
							'inclusive' => false,
						],
					];
					break;
			}
		}

		return [
			'date_query' => $date_query,
		];
	}
}
