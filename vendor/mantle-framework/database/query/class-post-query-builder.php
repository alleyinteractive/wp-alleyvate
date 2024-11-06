<?php
/**
 * Post_Query_Builder class file.
 *
 * @package Mantle
 *
 * @phpcs:disable Squiz.Commenting.FunctionComment
 */

namespace Mantle\Database\Query;

use Mantle\Database\Model\Term;
use Mantle\Database\Query\Concerns\Queries_Dates;
use Mantle\Support\Helpers;
use RuntimeException;
use WP_Term;

use function Mantle\Support\Helpers\collect;

/**
 * Post Query Builder
 *
 * @template TModel of \Mantle\Database\Model\Model
 * @extends \Mantle\Database\Query\Builder<TModel>
 *
 * @method \Mantle\Database\Query\Post_Query_Builder<TModel> anyStatus()
 * @method \Mantle\Database\Query\Post_Query_Builder<TModel> whereId( int $id )
 * @method \Mantle\Database\Query\Post_Query_Builder<TModel> whereName( string $name )
 * @method \Mantle\Database\Query\Post_Query_Builder<TModel> whereSlug( string $slug )
 * @method \Mantle\Database\Query\Post_Query_Builder<TModel> whereStatus( string[]|string $status )
 * @method \Mantle\Database\Query\Post_Query_Builder<TModel> whereTitle( string $title )
 * @method \Mantle\Database\Query\Post_Query_Builder<TModel> whereType( string $type )
 */
class Post_Query_Builder extends Builder {
	use Queries_Dates;
	use Queries_Relationships;

	/**
	 * Query Variable Aliases
	 */
	protected array $query_aliases = [
		'date_gmt'     => 'post_date_gmt',
		'date_utc'     => 'post_date_gmt',
		'date'         => 'post_date',
		'id'           => 'p',
		'modified_gmt' => 'post_modified_gmt',
		'modified_utc' => 'post_modified_gmt',
		'modified'     => 'post_modified',
		'post_author'  => 'author',
		'post_name'    => 'name',
		'slug'         => 'name',
		'status'       => 'post_status',
	];

	/**
	 * Query Where In Aliases
	 */
	protected array $query_where_in_aliases = [
		'author'      => 'author__in',
		'id'          => 'post__in',
		'post_name'   => 'post_name__in',
		'post_parent' => 'post_parent__in',
		'tag'         => 'tag__in',
		'tag_slug'    => 'tag_slug__in',
	];

	/**
	 * Query Where Not In Aliases
	 */
	protected array $query_where_not_in_aliases = [
		'author'      => 'author__not_in',
		'id'          => 'post__not_in',
		'post_name'   => 'post_name__not_in',
		'post_parent' => 'post_parent__not_in',
		'tag'         => 'tag__not_in',
		'tag_slug'    => 'tag_slug__not_in',
	];

	/**
	 * Query order by aliases.
	 */
	protected array $query_order_by_aliases = [
		'id' => 'ID',
	];

	/**
	 * Tax Query.
	 */
	protected array $tax_query = [];

	/**
	 * Get the query arguments.
	 */
	public function get_query_args(): array {
		$this->apply_scopes();

		if ( is_array( $this->model ) ) {
			$post_type = [];

			foreach ( $this->model as $model ) {
				$post_type[] = $model::get_object_name();
			}
		} else {
			$post_type = $this->model::get_object_name();
		}

		[ $order, $order_by ] = $this->get_builder_order( 'DESC', 'date' );

		return array_merge(
			[
				'ignore_sticky_posts' => true,
				'meta_query'          => $this->meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'order'               => $order,
				'orderby'             => $order_by,
				'paged'               => $this->page,
				'post_type'           => $post_type,
				'posts_per_page'      => $this->limit,
				'suppress_filters'    => false,
				'tax_query'           => $this->tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			],
			$this->get_date_query_args(),
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
		$query = new \WP_Query();

		// Store the query hash for reference by side-effects.
		$this->query_hash = spl_object_hash( $query );

		$this->with_clauses(
			fn () => $query->query( $this->get_query_args() ),
		);

		if ( empty( $query->found_posts ) && count( $query->posts ) > 0 ) {
			$this->found_rows = null;
		} else {
			$this->found_rows = $query->found_posts;
		}

		$post_ids = $query->posts;

		if ( empty( $post_ids ) ) {
			return ( new Collection() )->with_found_rows( $this->found_rows ); // @phpstan-ignore-line should return
		}

		$models = $this
			->get_models( $post_ids )
			->with_found_rows( $this->found_rows );

		// Return the models if there are no models or if multiple model instances
		// are used. Eager loading does not currently support multiple models.
		if ( $models->is_empty() || is_array( $this->model ) || empty( $this->eager_load ) ) {
			return $models;
		}

		return $this->eager_load_relations( $models );
	}

	/**
	 * Get the count of the query results.
	 */
	public function count(): int {
		$this->take( -1 );

		$query = new \WP_Query();

		// Store the query hash for reference by side-effects.
		$this->query_hash = spl_object_hash( $query );

		$this->with_clauses(
			fn () => $query->query( $this->get_query_args() ),
		);

		return $query->found_posts;
	}

	/**
	 * Retrieve hydrated models for the post IDs.
	 *
	 * @param int[] $post_ids Post IDs.
	 * @return Collection<int, TModel>
	 */
	protected function get_models( array $post_ids ): Collection {
		if ( is_array( $this->model ) ) {
			$model_object_types = $this->get_model_object_names();

			return Collection::from( $post_ids )
				->map(
					function ( $post_id ) use ( $model_object_types ) {
						$post_type = \get_post_type( $post_id );

						if ( empty( $model_object_types[ $post_type ] ) ) {
							throw new RuntimeException(
								"Missing model for object type [{ $post_type }]."
							);
						}

						if ( empty( $post_type ) ) {
							return null;
						}

						return $model_object_types[ $post_type ]::find( $post_id );
					}
				)
				->filter()
				->values();
		}

		return Collection::from( $post_ids )
			->map( [ $this->model, 'find' ] )
			->filter();
	}

	/**
	 * Include a taxonomy query.
	 *
	 * @param array|string|Term|\WP_Term|int $term Term ID/array of IDs.
	 * @param string                         $taxonomy Taxonomy name.
	 * @param string                         $operator Operator to use, defaults to 'IN'.
	 * @param string                         $field Field to use for the query, defaults to term ID.
	 * @return static
	 *
	 * @throws Query_Exception Unknown term to query against.
	 */
	public function whereTerm( $term, $taxonomy = null, string $operator = 'IN', string $field = 'term_id' ) {
		if ( $term instanceof Term ) {
			$taxonomy = $term->taxonomy();
			$term     = $term->id();
		}

		if ( $term instanceof \WP_Term ) {
			$taxonomy = $term->taxonomy;
			$term     = $term->term_id;
		}

		// Get the taxonomy if it wasn't passed.
		if ( empty( $taxonomy ) && ! is_array( $term ) ) {
			// Attempt to resolve the term from the slug.
			if ( 'slug' === $field ) {
				$object = get_term_by( 'slug', $term, $taxonomy );

				if ( ! ( $object instanceof WP_Term ) ) {
					throw new Query_Exception( 'Unknown term to query against with slug (must pass taxonomy): ' . $term );
				}
			} else {
				$object = Helpers\get_term_object( (int) $term );
			}

			if ( empty( $object ) ) {
				throw new Query_Exception( 'Unknown term: ' . $term );
			}

			$taxonomy = $object->taxonomy;
			$term     = $object->term_id;
		}

		$this->tax_query[] = [
			'field'            => $field,
			'include_children' => true,
			'operator'         => $operator,
			'taxonomy'         => $taxonomy,
			'terms'            => $term,
		];

		return $this;
	}

	/**
	 * Include a taxonomy query with the relation set to 'AND'.
	 *
	 * @param array|string $term Term ID/array of IDs.
	 * @param string       $taxonomy Taxonomy name.
	 * @param string       $operator Operator to use, defaults to 'IN'.
	 * @return static
	 */
	public function andWhereTerm( ...$args ) {
		$this->tax_query['relation'] = 'AND';
		return $this->whereTerm( ...$args );
	}

	/**
	 * Include a taxonomy query with the relation set to 'OR'.
	 *
	 * @param array|string $term Term ID/array of IDs.
	 * @param string       $taxonomy Taxonomy name.
	 * @param string       $operator Operator to use, defaults to 'IN'.
	 * @return static
	 */
	public function orWhereTerm( ...$args ) {
		$this->tax_query['relation'] = 'OR';
		return $this->whereTerm( ...$args );
	}

	/**
	 * Fetch the query with 'no_found_rows' set to a value.
	 *
	 * Setting to 'true' prevents counting all the available rows for a query.
	 *
	 * @param bool $value Whether to set 'no_found_rows' to true.
	 */
	public function withNoFoundRows( bool $value = true ): static {
		return $this->where( 'no_found_rows', $value );
	}

	/**
	 * Dump the SQL query being executed.
	 *
	 * @param bool $die Whether to die after dumping the SQL.
	 */
	public function dumpSql( bool $die = false ): static {
		add_filter(
			'posts_request',
			function ( string $sql, \WP_Query $query ) use ( $die ) {
				if ( spl_object_hash( $query ) === $this->query_hash ) {
					dump( $sql );

					if ( $die ) {
						die;
					}
				}

				return $sql;
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
