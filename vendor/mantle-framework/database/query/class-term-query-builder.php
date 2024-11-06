<?php
/**
 * Term_Query_Builder class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Query;

/**
 * Term Query Builder
 *
 * @template TModel of \Mantle\Database\Model\Model
 * @extends \Mantle\Database\Query\Builder<TModel>
 *
 * @method \Mantle\Database\Query\Term_Query_Builder whereId( int $id )
 * @method \Mantle\Database\Query\Term_Query_Builder whereName( string $name )
 * @method \Mantle\Database\Query\Term_Query_Builder whereSlug( string $slug )
 * @method \Mantle\Database\Query\Term_Query_Builder whereTaxonomy( string $taxonomy )
 */
class Term_Query_Builder extends Builder {
	use Queries_Relationships;

	/**
	 * Query Variable Aliases
	 */
	protected array $query_aliases = [
		'id'      => 'include',
		'term_id' => 'include',
	];

	/**
	 * Query Where In Aliases
	 */
	protected array $query_where_in_aliases = [
		'term_id' => 'include',
		'name'    => 'name',
		'slug'    => 'slug',
	];

	/**
	 * Query Where Not In Aliases
	 */
	protected array $query_where_not_in_aliases = [
		'name'    => 'name',
		'slug'    => 'slug',
		'term_id' => 'exclude',
	];

	/**
	 * Query order by aliases.
	 */
	protected array $query_order_by_aliases = [
		'id' => 'term_id',
	];

	/**
	 * Get the query arguments.
	 */
	public function get_query_args(): array {
		if ( is_array( $this->model ) ) {
			$taxonomies = [];

			foreach ( $this->model as $model ) {
				$taxonomies[] = $model::get_object_name();
			}
		} else {
			$taxonomies = $this->model::get_object_name();
		}

		// Limit is handled differently for term queries.
		if ( -1 === $this->limit ) {
			$this->limit = null;
		}

		[ $order, $order_by ] = $this->get_builder_order( 'ASC', 'name' );

		return array_merge(
			[
				'hide_empty'      => false,
				'meta_query'      => $this->meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'number'          => $this->limit,
				'order'           => $order,
				'orderby'         => $order_by,
				'suppress_filter' => false,
				'taxonomy'        => $taxonomies,
			],
			$this->wheres,
			[
				'fields' => 'ids',
			]
		);
	}

	/**
	 * Execute the query.
	 *
	 * @return Collection<int, TModel>
	 */
	public function get(): Collection {
		$query = new \WP_Term_Query();

		$this->query_hash = spl_object_hash( $query );

		/**
		 * Fetch the terms IDs for the query.
		 *
		 * @var int[]
		 */
		$term_ids = $this->with_clauses(
			fn (): array => $query->query( $this->get_query_args() ),
		);

		if ( empty( $term_ids ) ) {
			return new Collection(); // @phpstan-ignore-line should return
		}

		$models = array_map( [ $this->model, 'find' ], $term_ids );

		return $this->eager_load_relations(
			Collection::from( $models )->filter()->values(),
		);
	}

	/**
	 * Get the count of the query results.
	 */
	public function count(): int {
		$this->take( -1 );

		$query = new \WP_Term_Query();

		$this->query_hash = spl_object_hash( $query );

		return $this->with_clauses(
			fn (): int => (int) $query->query(
				array_merge(
					$this->get_query_args(),
					[
						'fields' => 'count',
					],
				),
			),
		);
	}

	/**
	 * Dump the SQL query being executed.
	 *
	 * @param bool $die Whether to die after dumping the SQL.
	 */
	public function dumpSql( bool $die = false ): static {
		add_filter(
			'terms_pre_query',
			function ( mixed $terms, \WP_Term_Query $query ) use ( $die ) {
				if ( spl_object_hash( $query ) === $this->query_hash ) {
					dump( $query->request );

					if ( $die ) {
						die;
					}
				}

				return $terms;
			},
			10,
			2
		);

		return $this;
	}

	/**
	 * Dump the SQL query being executed and die.
	 */
	public function ddSql(): never {
		$this->dumpSql( true )->get();

		die( 1 );
	}
}
