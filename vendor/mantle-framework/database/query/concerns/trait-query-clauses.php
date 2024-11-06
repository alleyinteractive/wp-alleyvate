<?php
/**
 * Query_Clauses trait file
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag, Squiz.Commenting.FunctionComment.ParamNameNoMatch
 *
 * @package Mantle
 */

namespace Mantle\Database\Query\Concerns;

use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Database\Query\Term_Query_Builder;

/**
 * Allow a query to be modified.
 *
 * Provides an way to modify the subsequent query performed by
 * WP_Query/WP_Term_Query/etc. in a controlled manner which will not affect
 * other queries.
 *
 * @mixin \Mantle\Database\Query\Builder
 */
trait Query_Clauses {
	/**
	 * Query clauses to be added to the query.
	 *
	 * @var array<int, callable(array<string>, \WP_Query|\WP_Term_Query $query): array<string>> The query clauses.
	 */
	protected array $clauses = [];

	/**
	 * Storage of the term query.
	 */
	protected ?\WP_Term_Query $query_clause_query = null;

	/**
	 * Add a clause to the query.
	 *
	 * @param callable(array<string>, \WP_Query|\WP_Term_Query $query): array<string> $clause The query clause.
	 */
	public function add_clause( callable $clause ): static {
		$this->clauses[] = $clause;

		return $this;
	}

	/**
	 * Remove all clauses from the query.
	 */
	public function clear_clauses(): static {
		$this->clauses = [];

		return $this;
	}

	/**
	 * Apply the query clauses to the query.
	 *
	 * @param array<string> $clauses The query clauses.
	 * @param mixed         ...$args Other arguments passed to the filter.
	 * @return array<string> The modified query clauses.
	 */
	public function apply_clauses( array $clauses, ...$args ): array {
		// Extract the query from the arguments.
		$query = $args[0] ?? null;

		if ( is_array( $query ) && isset( $this->query_clause_query ) ) {
			$query = $this->query_clause_query;
		}

		// Only apply the clauses if the query is set and the query hash matches.
		if ( ! $query || spl_object_hash( $query ) !== $this->get_query_hash() ) {
			return $clauses;
		}

		foreach ( $this->clauses as $clause ) {
			$clauses = $clause( $clauses, $query, ...$args );
		}

		return $clauses;
	}

	/**
	 * Register the query clauses before the query is executed.
	 */
	protected function register_clauses(): void {
		if ( $this instanceof Post_Query_Builder ) {
			add_filter( 'posts_clauses', [ $this, 'apply_clauses' ], 10, 2 );
		} elseif ( $this instanceof Term_Query_Builder ) {
			add_action( 'pre_get_terms', [ $this, 'on_pre_get_terms' ] );
		}
	}

	/**
	 * Unregister the query clauses after the query is executed.
	 */
	protected function unregister_clauses(): void {
		if ( $this instanceof Post_Query_Builder ) {
			remove_filter( 'posts_clauses', [ $this, 'apply_clauses' ], 10 );
		} elseif ( $this instanceof Term_Query_Builder ) {
			remove_action( 'pre_get_terms', [ $this, 'on_pre_get_terms' ], 10 );
			remove_filter( 'terms_clauses', [ $this, 'apply_clauses' ], 10 );

			// Reset the query object being stored.
			$this->query_clause_query = null;
		}
	}

	/**
	 * Execute a callback with the clauses applied only for the closure.
	 *
	 * @template TReturnValue
	 *
	 * @param callable(): TReturnValue $callback The callback to execute.
	 * @return TReturnValue The return value of the callback.
	 */
	protected function with_clauses( callable $callback ): mixed {
		// Skip if there are no clauses to apply.
		if ( empty( $this->clauses ) ) {
			return $callback();
		}

		$this->register_clauses();

		$result = $callback();

		$this->unregister_clauses();

		return $result;
	}

	/**
	 * Register the 'pre_get_terms' listener for a term query.
	 *
	 * As a workaround for the lack of query being passed to the 'terms_clauses'
	 * filter, we use the 'pre_get_terms' filter to register the 'terms_clauses'
	 * filter and store the query object.
	 *
	 * @param \WP_Term_Query $query The term query.
	 */
	public function on_pre_get_terms( \WP_Term_Query $query ): void {
		// Only apply the clauses if the query hash matches.
		if ( spl_object_hash( $query ) !== $this->get_query_hash() ) {
			return;
		}

		// Store the query object.
		$this->query_clause_query = $query;

		// Remove the 'pre_get_terms' filter.
		remove_action( 'pre_get_terms', [ $this, 'on_pre_get_terms' ] );

		// Register the 'terms_clauses' filter.
		add_filter( 'terms_clauses', [ $this, 'apply_clauses' ], 10, 3 );
	}
}
