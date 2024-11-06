<?php
/**
 * Builder class file.
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable Squiz.Commenting.VariableComment.Missing, Squiz.Commenting.FunctionComment
 * phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine, PEAR.Functions.FunctionCallSignature.MultipleArguments, PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket
 *
 * @package Mantle
 */

namespace Mantle\Database\Query;

use BackedEnum;
use Closure;
use Mantle\Container\Container;
use Mantle\Contracts\Database\Scope;
use Mantle\Contracts\Paginator\Paginator as PaginatorContract;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Model_Not_Found_Exception;
use Mantle\Database\Pagination\Length_Aware_Paginator;
use Mantle\Database\Pagination\Paginator;
use Mantle\Database\Query\Concerns\Query_Bindings;
use Mantle\Database\Query\Concerns\Query_Clauses;
use Mantle\Support\Arr;
use Mantle\Support\Collection;
use Mantle\Support\Str;
use Mantle\Support\Traits\Conditionable;

/**
 * Builder Query Builder
 *
 * @template TModel of \Mantle\Database\Model\Model
 */
abstract class Builder {
	use Conditionable;
	use Query_Bindings;
	use Query_Clauses;

	/**
	 * Result limit per-page.
	 */
	protected ?int $limit = 100;

	/**
	 * Result offset.
	 */
	protected int $offset = 0;

	/**
	 * Result page.
	 */
	protected int $page = 1;

	/**
	 * Where arguments for the query.
	 */
	protected array $wheres = [];

	/**
	 * Order of the query.
	 *
	 * @var array<int, string>
	 */
	protected array $order = [];

	/**
	 * Query by of the query.
	 *
	 * @var array<int, string>
	 */
	protected array $order_by = [];

	/**
	 * Meta Query.
	 */
	protected array $meta_query = [];

	/**
	 * Query Variable Aliases
	 */
	protected array $query_aliases = [];

	/**
	 * Query Where In Aliases
	 */
	protected array $query_where_in_aliases = [];

	/**
	 * Query Where Not In Aliases
	 */
	protected array $query_where_not_in_aliases = [];

	/**
	 * Query order by aliases.
	 */
	protected array $query_order_by_aliases = [];

	/**
	 * Applied global scopes.
	 */
	protected array $scopes = [];

	/**
	 * Storage of the found rows for a query.
	 */
	protected ?int $found_rows = 0;

	/**
	 * Relationships to eager load.
	 *
	 * @var string[]
	 */
	protected array $eager_load = [];

	/**
	 * Query hash for the built query.
	 */
	protected string $query_hash = '';

	/**
	 * Constructor.
	 *
	 * @param string|string[] $model Model name or array of model names.
	 * @phpstan-param class-string<TModel>|array<class-string<TModel>> $model
	 */
	public function __construct( protected array|string $model ) {}

	/**
	 * Get the query results.
	 *
	 * @return Collection<int, TModel>
	 */
	abstract public function get(): Collection;

	/**
	 * Get the count of the query results.
	 */
	abstract public function count(): int;

	/**
	 * Get the query arguments.
	 */
	abstract public function get_query_args(): array;

	/**
	 * Dump the SQL query for the request.
	 */
	abstract public function dumpSql(): static;

	/**
	 * Dump the SQL query for the request and stop execution.
	 */
	abstract public function ddSql(): never;

	/**
	 * Get a model instance for the builder.
	 *
	 * @return string|string[]
	 */
	public function get_model() {
		return $this->model;
	}

	/**
	 * Get the model instance for the builder.
	 *
	 * @throws Query_Exception Thrown when trying to use with multiple models.
	 */
	protected function get_model_instance(): Model {
		if ( is_array( $this->model ) ) {
			throw new Query_Exception( 'Unable to get model instance for multiple models.' );
		}

		return new $this->model();
	}

	/**
	 * Retrieve the found rows for a query.
	 */
	public function get_found_rows(): int {
		return $this->found_rows;
	}

	/**
	 * Query an attribute against a list.
	 *
	 * @param string $attribute Attribute to query against.
	 * @param array  $values List of values.
	 * @return static
	 *
	 * @throws Query_Exception Thrown on an unmapped attribute being used.
	 */
	public function whereIn( string $attribute, array $values ) {
		if ( is_string( $this->model ) && $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		if ( ! empty( $this->query_where_in_aliases[ strtolower( $attribute ) ] ) ) {
			$attribute = $this->query_where_in_aliases[ strtolower( $attribute ) ];
		} else {
			throw new Query_Exception( 'Unknown where in alias: ' . $attribute );
		}

		return $this->where( $attribute, $values );
	}

	/**
	 * Query an where an attribute is not in a list.
	 *
	 * @param string $attribute Attribute to query against.
	 * @param array  $values List of values.
	 * @return static
	 *
	 * @throws Query_Exception Thrown on an unmapped attribute being used.
	 */
	public function whereNotIn( string $attribute, array $values ) {
		if ( is_string( $this->model ) && $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		if ( ! empty( $this->query_where_not_in_aliases[ strtolower( $attribute ) ] ) ) {
			$attribute = $this->query_where_not_in_aliases[ strtolower( $attribute ) ];
		} else {
			throw new Query_Exception( 'Unknown where not in alias: ' . $attribute );
		}

		return $this->where( $attribute, $values );
	}

	/**
	 * Create a query builder for a model.
	 *
	 * @param array|string $model Model name or array of model names.
	 * @return static
	 */
	public static function create( $model ) {
		return new static( $model );
	}

	/**
	 * Add a where clause to the query.
	 *
	 * @param string|array $attribute Attribute to use or array of key => value
	 *                                attributes to set.
	 * @param mixed        $value Value to compare against.
	 */
	public function where( array|string $attribute, mixed $value = '' ): static {
		if ( is_array( $attribute ) && empty( $value ) ) {
			foreach ( $attribute as $key => $value ) {
				$this->where( $key, $value );
			}

			return $this;
		}

		$attribute = $this->resolve_attribute( $attribute );

		// Pass date attributes to the date query builder if available.
		if ( method_exists( $this, 'whereDate' ) && in_array( $attribute, [ 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt' ], true ) ) {
			return $this->whereDate( $value, '=', $attribute );
		}

		$this->wheres[ $attribute ] = $value;

		return $this;
	}

	/**
	 * Support a dynamic where query.
	 *
	 * @param string $method Method name.
	 * @param array  $args Arguments.
	 * @return static
	 */
	public function dynamicWhere( $method, $args ) {
		$finder = substr( $method, 5 );

		$attribute = Str::snake( $finder );

		// Use the model's alias if one exist.
		if ( is_string( $this->model ) && $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		return $this->where( $attribute, ...$args );
	}

	/**
	 * Resolve an attribute name to the database column name with support for
	 * query aliases.
	 *
	 * @param string $attribute Attribute name.
	 */
	protected function resolve_attribute( string $attribute ): string {
		if ( ! empty( $this->query_aliases[ strtolower( $attribute ) ] ) ) {
			return $this->query_aliases[ strtolower( $attribute ) ];
		}

		return $attribute;
	}

	/**
	 * Query by a meta field.
	 *
	 * @param string|\BackedEnum $key Meta key.
	 * @param mixed              $value Meta value.
	 * @param string             $compare Comparison method, defaults to '='.
	 * @return static
	 */
	public function whereMeta( $key, $value, string $compare = '=' ) {
		if ( $key instanceof BackedEnum ) {
			$key = $key->value;
		}

		if ( $value instanceof BackedEnum ) {
			$value = $value->value;
		}

		$meta_query = [
			'compare' => $compare,
			'key'     => $key,
			'value'   => $value,
		];

		// Remove the value from meta queries checking for existence.
		if ( empty( $value ) && ( 'EXISTS' === $compare || 'NOT EXISTS' === $compare ) ) {
			unset( $meta_query['value'] );
		}

		$this->meta_query[] = $meta_query;

		return $this;
	}

	/**
	 * Query by a meta field with the relation set to 'and'.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @param string $compare Comparison method, defaults to '='.
	 * @return static
	 */
	public function andWhereMeta( ...$args ) {
		$this->meta_query['relation'] = 'AND';

		return $this->whereMeta( ...$args );
	}

	/**
	 * Query by a meta field with the relation set to 'or'.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @param string $compare Comparison method, defaults to '='.
	 * @return static
	 */
	public function orWhereMeta( ...$args ) {
		$this->meta_query['relation'] = 'OR';

		return $this->whereMeta( ...$args );
	}

	/**
	 * Order the query by a field.
	 *
	 * @param string $attribute Attribute name.
	 * @param string $direction Order direction.
	 * @return static
	 */
	public function orderBy( string $attribute, string $direction = 'asc' ) {
		if ( is_string( $this->model ) && $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		if ( ! empty( $this->query_order_by_aliases[ strtolower( $attribute ) ] ) ) {
			$attribute = $this->query_order_by_aliases[ strtolower( $attribute ) ];
		}

		$this->order[]    = strtoupper( $direction );
		$this->order_by[] = $attribute;

		return $this;
	}

	/**
	 * Alias for `orderBy()`.
	 *
	 * @param string $attribute Attribute name.
	 * @param string $direction Order direction.
	 */
	public function order_by( string $attribute, string $direction = 'asc' ): static {
		return $this->orderBy( $attribute, $direction );
	}

	/**
	 * Reorder the query and remove existing order by clauses.
	 */
	public function removeOrder(): static {
		$this->order_by = [];
		$this->order    = [];

		return $this;
	}

	/**
	 * Alias for `removeOrder()`.
	 */
	public function remove_order(): static {
		return $this->removeOrder();
	}

	/**
	 * Support a dynamic order by (orderByName(...)).
	 *
	 * @param string $method Method name. Attribute name.
	 * @param array $args Method arguments.
	 * @return static
	 */
	protected function dynamicOrderBy( string $method, array $args ) {
		$attribute = Str::snake( substr( $method, 7 ) );

		$attribute = str_replace( '_in', '__in', $attribute );

		return $this->orderBy( $attribute, $args[0] ?? 'asc' );
	}

	/**
	 * Retrieve the builder order to use in the query.
	 *
	 * Used internally to get the order/order by for use in term/post queries.
	 * Queries support multiple conditions to order by but run into issues in some
	 * cases where arrays are used in place of strings (post__in for example). To
	 * support both, we'll store the order/order by as arrays and then flatten it
	 * here if only one pair is set.
	 *
	 * @param string $default_order Default order.
	 * @param string $default_order_by Default order by.
	 * @return array{0: string, 1: string}
	 */
	protected function get_builder_order( string $default_order, string $default_order_by ): array {
		$order    = count( $this->order ) > 1 ? $this->order : Arr::first( $this->order );
		$order_by = count( $this->order_by ) > 1 ? $this->order_by : Arr::first( $this->order_by );

		// Provide a default order by if none is set.
		if ( empty( $order ) ) {
			$order = $default_order;
		}

		// Provide a default order by if none is set.
		if ( empty( $order_by ) ) {
			$order_by = $default_order_by;
		}

		return [ $order, $order_by ];
	}

	/**
	 * Order by the value passed in `whereIn()`.
	 *
	 * @param string $attribute Attribute to use.
	 * @return static
	 *
	 * @throws Query_Exception Thrown on unknown alias.
	 */
	public function orderByWhereIn( string $attribute ) {
		if ( is_string( $this->model ) && $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		if ( ! empty( $this->query_where_in_aliases[ strtolower( $attribute ) ] ) ) {
			$attribute = $this->query_where_in_aliases[ strtolower( $attribute ) ];
		} else {
			throw new Query_Exception( 'Unknown where in alias: ' . $attribute );
		}

		return $this->orderBy( $attribute );
	}

	/**
	 * Alias for `orderByWhereIn()`.
	 *
	 * @param string $attribute Attribute to use.
	 */
	public function order_by_where_in( string $attribute ): static {
		return $this->orderByWhereIn( $attribute );
	}

	/**
	 * Determine if the given model has a scope.
	 *
	 * @param string $scope Scope name.
	 */
	public function has_named_scope( string $scope ): bool {
		// Disable model scopes for multi-model queries.
		if ( is_array( $this->model ) ) {
			return false;
		}

		return $this->get_model_instance()->has_named_scope( $scope );
	}

	/**
	 * Apply the given scope on the current builder instance.
	 *
	 * @param callable $scope Scope callback.
	 * @param array    $parameters Scope parameters.
	 * @return mixed
	 */
	protected function call_scope( callable $scope, array $parameters = [] ) {
		array_unshift( $parameters, $this );

		return $scope( ...array_values( $parameters ) ) ?? $this;
	}

	/**
	 * Apply the given named scope on the current builder instance.
	 *
	 * @param string $scope Scope name.
	 * @param array  $parameters Scope parameters.
	 * @return mixed
	 */
	protected function call_named_scope( string $scope, array $parameters = [] ) {
		return $this->call_scope(
			fn ( ...$parameters ) => $this->get_model_instance()->call_named_scope( $scope, $parameters ),
			$parameters
		);
	}

	/**
	 * Apply the scopes to the Eloquent builder instance and return it.
	 *
	 * @return static
	 */
	protected function apply_scopes() {
		// Ignore query builders across multiple models.
		if ( is_array( $this->model ) || empty( $this->scopes ) ) {
			return $this;
		}

		foreach ( $this->scopes as $scope ) {
			$this->call_scope(
				function ( self $builder ) use ( $scope ) {
					if ( $scope instanceof Closure ) {
						return $scope( $builder );
					}

					if ( $scope instanceof Scope ) {
						return $scope->apply( $builder, $this->get_model_instance() );
					}
				}
			);
		}

		return $this;
	}

	/**
	 * Register a new global scope.
	 *
	 * @param  string        $identifier Scope name.
	 * @param Scope|\Closure $scope Scope callback.
	 * @return static
	 */
	public function with_global_scope( string $identifier, $scope ) {
		$this->scopes[ $identifier ] = $scope;

		return $this;
	}

	/**
	 * Set the limit of objects to include.
	 *
	 * @param int $limit Limit to set.
	 * @return static
	 */
	public function take( int $limit ) {
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Set the current page of the builder.
	 *
	 * @param int $page Page to set.
	 * @return static
	 */
	public function page( int $page ) {
		$this->page = $page;

		return $this;
	}

	/**
	 * Set the page and limit of the builder.
	 *
	 * @param int $page Page to set.
	 * @param int $limit Limit to set.
	 */
	public function for_page( int $page, int $limit = 20 ): static {
		return $this->page( $page )->take( $limit );
	}

	/**
	 * Constrain the query to the next "page" of results after a given ID.
	 *
	 * @param int      $per_page Per page to set.
	 * @param int|null $last_id Last ID to use.
	 * @param string   $column Column to use.
	 */
	public function for_page_after_id( int $per_page, ?int $last_id = null, string $column = 'id' ): static {
		if ( ! is_null( $last_id ) ) {
			$this->where_raw( $column, '>', $last_id );
		}

		return $this
			->page( 1 ) // Ensure this query is never paged.
			->removeOrder()
			->orderBy( $column, 'asc' )
			->take( $per_page );
	}

	/**
	 * Get the first result of the query.
	 *
	 * @return TModel|null
	 */
	public function first(): ?Model {
		return $this->take( 1 )->get()->first();
	}

	/**
	 * Execute the query and get the first result or throw an exception.
	 *
	 * @return TModel|\Mantle\Database\Model\Model
	 * @throws Model_Not_Found_Exception Throws exception if not found.
	 *
	 * @phpstan-return TModel
	 */
	public function firstOrFail() {
		$model = $this->first();

		if ( ! $model ) {
			throw new Model_Not_Found_Exception( $this->model );
		}

		return $model;
	}

	/**
	 * Alias for firstOrFail().
	 *
	 * @return TModel|\Mantle\Database\Model\Model
	 * @throws Model_Not_Found_Exception Throws exception if not found.
	 *
	 * @phpstan-return TModel
	 */
	public function first_or_fail() {
		return $this->firstOrFail();
	}

	/**
	 * Get all the results of a query.
	 *
	 * @return Collection
	 */
	public function all(): Collection {
		return $this->take( -1 )->get();
	}

	/**
	 * Delete the results of this query.
	 *
	 * @param bool $force Flag to force delete.
	 */
	public function delete( bool $force = false ): void {
		$this->all()->each->delete( $force ); // @phpstan-ignore-line undefined method
	}

	/**
	 * Create a simple paginator instance for the current query.
	 *
	 * @param int $per_page Items per page.
	 * @param int $current_page Current page number.
	 */
	public function simple_paginate( int $per_page = 20, int $current_page = null ): PaginatorContract {
		return Container::get_instance()->make(
			Paginator::class,
			[
				'builder'      => $this,
				'current_page' => $current_page,
				'per_page'     => $per_page,
			]
		);
	}

	/**
	 * Create a length-aware paginator instance for the current query.
	 *
	 * @param int $per_page Items per page.
	 * @param int $current_page Current page number.
	 */
	public function paginate( int $per_page = 20, int $current_page = null ): PaginatorContract {
		return Container::get_instance()->make(
			Length_Aware_Paginator::class,
			[
				'builder'      => $this,
				'current_page' => $current_page,
				'per_page'     => $per_page,
			]
		);
	}

	/**
	 * Chunk the results of the query.
	 *
	 * Note: this method uses pagination and not suited for operations where
	 * data would be deleted which would affect subsequent pagination. For those
	 * operations, use the {@see Builder::chunk_by_id()} method.
	 *
	 * @param int                                                           $count Number of items to chunk by.
	 * @param callable(\Mantle\Support\Collection<int, TModel>, int): mixed $callback Callback to run on each chunk.
	 */
	public function chunk( int $count, callable $callback ): bool {
		$page = 1;

		do {
			$results = $this->page( $page )->take( $count )->get();

			$count_results = $results->count();

			if ( 0 === $count_results ) {
				return true;
			}

			// If the callback returns false, we'll stop traversing the results and
			// return false from the chunk method. This is useful for selectively
			// breaking the iteration when the callback no longer needs more data.
			if ( false === $callback( $results, $page ) ) {
				return false;
			}

			$page++;
		} while ( $count_results === $count );

		return true;
	}

	/**
	 * Chunk the results of the query by ID.
	 *
	 * This query handles chunking data where the data is could be
	 * deleted/modified during the chunking process.
	 *
	 * @param int                                                           $count Number of items to chunk by.
	 * @param callable(\Mantle\Support\Collection<int, TModel>, int): mixed $callback Callback to run on each chunk.
	 * @param string                                                        $attribute Attribute to chunk by.
	 */
	public function chunk_by_id( int $count, callable $callback, string $attribute = 'id' ): bool {
		$last_id = null;
		$page    = 1;

		do {
			$clone = clone $this;

			$results = $clone->for_page_after_id( $count, $last_id, $attribute )->get();

			$count_results = $results->count();

			if ( 0 === $count_results ) {
				return true;
			}

			// If the callback returns false, we'll stop traversing the results and
			// return false from the chunk method. This is useful for selectively
			// breaking the iteration when the callback no longer needs more data.
			if ( false === $callback( $results, $page ) ) {
				return false;
			}

			$page++;

			// Get the last item in the results.
			$last_item = $results->last();

			// Get the last item's ID.
			$last_id = $last_item->{$attribute};

			// Free up memory.
			unset( $clone, $results );
		} while ( $count_results === $count );

		return true;
	}

	/**
	 * Execute a callback over each item while chunking.
	 *
	 * @param callable(TModel): mixed $callback Callback to run on each chunk.
	 * @param int                     $count Number of items to chunk by.
	 * @return boolean
	 */
	public function each( callable $callback, int $count = 100 ) {
		return $this->chunk( $count, function ( Collection $results ) use ( $callback ): bool {
			foreach ( $results as $result ) {
				if ( false === $callback( $result ) ) {
					return false;
				}
			}

			return true;
		} );
	}

	/**
	 * Execute a callback over each item while chunking by ID.
	 *
	 * @param callable(TModel): mixed $callback Callback to run on each chunk.
	 * @param int                     $count Number of items to chunk by.
	 * @param string                  $attribute Attribute to chunk by.
	 * @return boolean
	 */
	public function each_by_id( callable $callback, int $count = 100, string $attribute = 'id' ) {
		return $this->chunk_by_id( $count, function ( Collection $results ) use ( $callback ): bool {
			foreach ( $results as $result ) {
				if ( false === $callback( $result ) ) {
					return false;
				}
			}

			return true;
		}, $attribute );
	}

	/**
	 * Map the query results to a new collection.
	 *
	 * @template TMapValue
	 *
	 * @param callable(TModel): TMapValue $callback Callback to run on each chunk.
	 * @param int                     $count Number of items to chunk by.
	 * @return Collection<int, TMapValue>
	 */
	public function map( callable $callback, int $count = 100 ) {
		$results = new Collection();

		$this->chunk( $count, function ( Collection $items ) use ( $callback, $results ): bool {
			$results->push( ...$items->map( $callback ) );

			return true;
		} );

		return $results;
	}

	/**
	 * Magic method to proxy to the appropriate query method.
	 *
	 * @param string $method Method name.
	 * @param array  $args Method arguments.
	 * @return mixed
	 *
	 * @throws Query_Exception Unknown query method called.
	 */
	public function __call( $method, $args ) {
		if ( Str::starts_with( $method, 'where' ) ) {
			return $this->dynamicWhere( $method, $args );
		}

		if ( Str::starts_with( $method, 'orderBy' ) ) {
			return $this->dynamicOrderBy( $method, $args );
		}

		// Check if the model has a local/named scope.
		if ( $this->has_named_scope( $method ) ) {
			return $this->call_named_scope( $method, $args );
		}

		throw new Query_Exception( 'Unknown query builder method: ' . $method );
	}

	/**
	 * Collect all the model object names in an associative Collection.
	 *
	 * @return Collection<string, class-string<\Mantle\Database\Model\Model>> Collection of model class names keyed by object name.
	 */
	public function get_model_object_names(): Collection {
		return ( new Collection( (array) $this->model ) ) // @phpstan-ignore-line should return
			->combine( $this->model )
			->map(
				fn ( $model ) => $model::get_object_name(),
			)
			->flip();
	}

	/**
	 * Retrieve the hash of the query object.
	 */
	public function get_query_hash(): string {
		return $this->query_hash;
	}

	/**
	 * Begin a query with eager loading.
	 *
	 * @param string ...$relations Relations to eager load.
	 * @return static
	 */
	public function with( ...$relations ) {
		$this->eager_load = array_merge( $this->eager_load, $relations );
		return $this;
	}

	/**
	 * Begin a query without eager loading relationships.
	 *
	 * @param string ...$relations Relations to not eager load.
	 * @return static
	 */
	public function without( ...$relations ) {
		$this->eager_load = array_diff_key( $this->eager_load, array_flip( $relations ) );
		return $this;
	}

	/**
	 * Dump the query variables being passed to WP_Query.
	 */
	public function dump(): static {
		dump( $this->get_query_args() );

		return $this;
	}

	/**
	 * Dump the query variables being passed to WP_Query and die.
	 */
	public function dd(): never {
		$this->dump();
		die;
	}

	/**
	 * Check if any models are found for the current query.
	 */
	public function exists(): bool {
		return $this->count() > 0;
	}

	/**
	 * Check if no models are found for the current query.
	 */
	public function doesntExist(): bool {
		return ! $this->exists();
	}

	/**
	 * Alias for `doesntExists()`.
	 */
	public function doesnt_exist(): bool {
		return $this->doesntExist();
	}
}
