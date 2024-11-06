<?php
/**
 * Query_Bindings trait file
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Database\Query\Concerns;

use InvalidArgumentException;

use function Mantle\Support\Helpers\collect;

/**
 * Allow a query to use raw bindings in a controlled manner.
 *
 * @todo Add nested queries.
 *
 * @mixin \Mantle\Database\Query\Builder
 */
trait Query_Bindings {
	/**
	 * Raw query bindings.
	 */
	protected array $bindings = [
		'where' => [],
	];

	/**
	 * The valid operators for a raw query binding.
	 */
	protected array $operators = [
		'=',
		'!=',
		'>',
		'>=',
		'<',
		'<=',
		'LIKE',
		'NOT LIKE',
		'IN',
		'NOT IN',
	];

	/**
	 * Flag to indicate if a raw query clause has been added.
	 */
	protected bool $raw_query_clause_added = false;

	/**
	 * Add a raw query binding.
	 *
	 * Allows the query to be built with raw SQL bindings.
	 *
	 * @param array|string $column The column name or array of bindings.
	 * @param string|null  $operator The operator OR the value if no value is provided.
	 * @param mixed        $value The value.
	 * @param string       $boolean The boolean operator (AND/OR) used to concatenate the clause.
	 */
	public function where_raw( array|string $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND' ): static {
		if ( is_array( $column ) ) {
			foreach ( $column as $value ) {
				$this->where_raw( ...array_values( $value ) );
			}

			return $this;
		}

		if ( is_null( $value ) && ! is_null( $operator ) ) {
			$value    = $operator;
			$operator = '=';
		}

		$this->bindings['where'][] = [
			'boolean'  => $boolean,
			'column'   => $column,
			'operator' => $operator,
			'value'    => $value,
		];

		$this->add_raw_query_clause();

		return $this;
	}

	/**
	 * Alias for where_raw().
	 *
	 * @param array|string $column The column name or array of bindings.
	 * @param string|null  $operator The operator OR the value if no value is provided.
	 * @param mixed        $value The value.
	 * @param string       $boolean The boolean operator (AND/OR) used to concatenate the clause.
	 */
	public function whereRaw( array|string $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND' ): static {
		return $this->where_raw( $column, $operator, $value, $boolean );
	}

	/**
	 * Construct a WHERE clause with a boolean OR.
	 *
	 * @param array|string $column The column name or array of bindings.
	 * @param string|null  $operator The operator OR the value if no value is provided.
	 * @param mixed        $value The value.
	 */
	public function or_where_raw( array|string $column, ?string $operator = null, mixed $value = null ): static {
		if ( is_array( $column ) ) {
			foreach ( $column as $value ) {
				$this->or_where_raw( ...array_values( $value ) );
			}

			return $this;
		}

		return $this->where_raw( $column, $operator, $value, 'OR' );
	}

	/**
	 * Alias for or_where_raw().
	 *
	 * @param array|string $column The column name or array of bindings.
	 * @param string|null  $operator The operator OR the value if no value is provided.
	 * @param mixed        $value The value.
	 */
	public function orWhereRaw( array|string $column, ?string $operator = null, mixed $value = null ): static {
		return $this->or_where_raw( $column, $operator, $value );
	}

	/**
	 * Add the raw query arguments to the query.
	 */
	public function add_raw_query_clause(): void {
		if ( $this->raw_query_clause_added ) {
			return;
		}

		$this->add_clause(
			fn ( array $clauses, \WP_Query|\WP_Term_Query $query ) => $this->apply_query_bindings( $clauses, $query )
		);

		$this->raw_query_clause_added = true;
	}

	/**
	 * Apply the query bindings to the clauses of a query.
	 *
	 * @throws InvalidArgumentException If the query class is invalid.
	 *
	 * @param array                    $clauses The query clauses.
	 * @param \WP_Query|\WP_Term_Query $query The query object.
	 * @return array The modified query clauses.
	 */
	protected function apply_query_bindings( array $clauses, \WP_Query|\WP_Term_Query $query ): array {
		global $wpdb;

		$table = match ( $query::class ) {
			\WP_Query::class      => $wpdb->posts,
			\WP_Term_Query::class => $wpdb->terms,
			default               => throw new InvalidArgumentException( 'Invalid query class: ' . $query::class ),
		};

		foreach ( $this->bindings['where'] as $binding ) {
			$clauses['where'] .= $this->get_where_clause( $table, $binding );
		}

		return $clauses;
	}

	/**
	 * Get the where clause for a binding.
	 *
	 * @throws InvalidArgumentException If the operator is invalid.
	 *
	 * @param string $table The table name.
	 * @param array  $binding The binding.
	 * @return string The where clause.
	 */
	protected function get_where_clause( string $table, array $binding ): string {
		global $wpdb;

		$boolean  = $binding['boolean'];
		$column   = $binding['column'];
		$operator = $binding['operator'];
		$value    = $binding['value'];

		if ( ! in_array( $operator, $this->operators, true ) ) {
			throw new InvalidArgumentException( esc_html( "Invalid operator for raw query binding: {$operator}" ) );
		}

		// Handle an array of values, commonly used with IN/NOT IN.
		if ( is_array( $value ) ) {
			$value = collect( $value )
				->map( 'esc_sql' )
				->implode( ', ' );

			return " {$boolean} {$table}.{$column} {$operator} ({$value})";
		}

		return $wpdb->prepare(
			" {$boolean} {$table}.{$column} {$operator} %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$value
		);
	}
}
