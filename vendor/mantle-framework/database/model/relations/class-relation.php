<?php
/**
 * Relation class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Relations;

use Closure;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Term;
use Mantle\Database\Query\Builder;
use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Database\Query\Term_Query_Builder;
use Mantle\Support\Collection;
use Mantle\Support\Forward_Calls;

/**
 * Relation base class.
 *
 * @mixin \Mantle\Database\Query\Builder
 *
 * @template TParent of Model
 */
abstract class Relation {
	use Forward_Calls;

	/**
	 * Internal taxonomy for post-to-post relationships.
	 *
	 * @var string
	 */
	public const RELATION_TAXONOMY = 'mantle_relationship';

	/**
	 * Query Builder instance.
	 *
	 * @var Builder
	 */
	protected $query;

	/**
	 * The related model (child).
	 *
	 * @var string
	 */
	protected $related;

	/**
	 * Flag if the relation uses terms.
	 *
	 * @var bool|null
	 */
	protected $uses_terms;

	/**
	 * Model's relationship name.
	 *
	 * @var string|null
	 */
	protected $relationship;

	/**
	 * Indicates if the relation is adding constraints.
	 *
	 * @var bool
	 */
	protected static $constraints = true;

	/**
	 * Create a new relation instance.
	 *
	 * @param Builder   $query Query builder instance.
	 * @param Model     $parent Model instance.
	 * @param bool|null $uses_terms Flag if the relation uses terms.
	 * @param string    $relationship Relationship name, optional.
	 */
	public function __construct( Builder $query, protected Model $parent, ?bool $uses_terms = null, string $relationship = null ) {
		$this->query   = $query;
		$this->related = $query->get_model();

		if ( ! is_null( $uses_terms ) ) {
			$this->uses_terms( $uses_terms );
		}

		$this->relationship = $relationship ?: $this->guess_relationship();
	}

	/**
	 * Run a callback with constraints disabled on the relation.
	 *
	 * @param Closure $callback Callback to invoke.
	 * @return mixed
	 */
	public static function no_constraints( Closure $callback ) {
		$previous = static::$constraints;

		static::$constraints = false;

		try {
			return $callback();
		} finally {
			static::$constraints = $previous;
		}
	}


	/**
	 * Set the query constraints to apply to the query.
	 *
	 * @return void
	 */
	abstract public function add_constraints();

	/**
	 * Set the query constraints for an eager load of the relation.
	 *
	 * @param Collection $models Models to eager load for.
	 */
	abstract public function add_eager_constraints( Collection $models ): void;

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	abstract public function get_results();

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param Collection $models Parent models.
	 * @param Collection $results Eagerly loaded results to match.
	 */
	abstract public function match( Collection $models, Collection $results ): Collection;

	/**
	 * Retrieve the query for a relation.
	 *
	 * @return Builder;
	 */
	public function get_query(): Builder {
		return $this->query;
	}

	/**
	 * Get the relationship for eager loading.
	 */
	public function get_eager(): Collection {
		return $this->query->get();
	}

	/**
	 * Handle dynamic method calls to the relationship.
	 *
	 * @param string $method Method name.
	 * @param array  $parameters Method arguments.
	 * @return mixed
	 */
	public function __call( string $method, array $parameters ) {
		$this->add_constraints();

		$result = $this->forward_call_to( $this->query, $method, $parameters );

		if ( $this->query === $result ) {
			return $this;
		}

		return $result;
	}

	/**
	 * Flag if the relation uses terms.
	 *
	 * @param bool $uses Flag if the relation uses or doesn't use terms.
	 * @return static
	 */
	public function uses_terms( bool $uses = true ) {
		$this->uses_terms = $uses;
		return $this;
	}

	/**
	 * Guess the name of the relationship.
	 */
	protected function guess_relationship(): ?string {
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 5 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

		foreach ( $trace as $item ) {
			if ( is_subclass_of( $item['class'], Model::class ) ) {
				return $item['function'];
			}
		}

		return null;
	}

	/**
	 * Determine if this is a post -> term relationship.
	 */
	protected function is_post_term_relationship(): bool {
		return $this->parent instanceof Post && $this->query instanceof Term_Query_Builder;
	}

	/**
	 * Determine if this is a term -> post relationship.
	 */
	protected function is_term_post_relationship(): bool {
		return $this->parent instanceof Term && $this->query instanceof Post_Query_Builder;
	}
}
