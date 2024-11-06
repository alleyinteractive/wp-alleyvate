<?php
/**
 * Belongs_To class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Relations;

use Mantle\Contracts\Database\Core_Object;
use Mantle\Contracts\Database\Model_Meta;
use Mantle\Contracts\Database\Updatable;
use Mantle\Database\Model\Model;
use Mantle\Database\Model\Model_Exception;
use Mantle\Database\Query\Builder;
use Mantle\Support\Collection;
use Mantle\Support\Str;
use RuntimeException;
use Throwable;
use WP_Term;

use function Mantle\Support\Helpers\collect;

/**
 * Creates a 'Belongs To' relationship.
 * Performs a meta query on the parent model with data from the current model.
 *
 * Example: Search the parent post's meta query with the ID of the current model.
 *
 * For relationships between posts and term models, the Belongs To relationship
 * is not supported for performance reasons.
 */
class Belongs_To extends Relation {
	/**
	 * Local key.
	 *
	 * @var string
	 */
	protected $local_key;

	/**
	 * Foreign key.
	 *
	 * @var string
	 */
	protected $foreign_key;

	/**
	 * Create a new has one or many relationship instance.
	 *
	 * @param Builder $query Query builder object.
	 * @param Model   $parent Parent model.
	 * @param string  $foreign_key Foreign key.
	 * @param string  $local_key Local key.
	 */
	public function __construct( Builder $query, Model $parent, string $foreign_key, ?string $local_key = null ) {
		$this->foreign_key = $foreign_key;
		$this->local_key   = $local_key;

		parent::__construct( $query, $parent );
	}

	/**
	 * Add constraints to the query.
	 */
	public function add_constraints(): void {
		if ( ! static::$constraints ) {
			return;
		}

		if ( $this->uses_terms ) {
			$object_ids = $this->get_term_ids_for_relationship();

			if ( empty( $object_ids ) ) {
				// Prevent the query from going through.
				// @todo Handle this better.
				$this->query->where( 'id', PHP_INT_MAX );
			} else {
				$this->query->whereIn( 'id', $object_ids );
			}

			return;
		} elseif ( $this->parent instanceof Model_Meta ) {
			$meta_value = $this->parent->get_meta( $this->local_key );

			if ( empty( $meta_value ) ) {
				/**
				 * Prevent the query from going through.
				 *
				 * @todo Handle missing meta value better.
				 */
				$this->query->where( 'id', PHP_INT_MAX );
			} else {
				$this->query->where( $this->foreign_key, $meta_value );
			}
		}
	}

	/**
	 * Set the query constraints for an eager load of the relation.
	 *
	 * @param Collection $models Models to eager load for.
	 *
	 * @throws RuntimeException Thrown on eager loading term relationships.
	 */
	public function add_eager_constraints( Collection $models ): void {
		if ( $this->uses_terms ) {
			throw new RuntimeException( 'Eager loading relationships with terms is not supported yet.' );
		} else {
			$append = $this->should_append();

			$meta_values = $models
				->map(
					fn ( $model ) => $model->get_meta( $this->local_key, ! $append )
				)
				->filter();

			if ( $append ) {
				$meta_values = $meta_values->collapse();
			}

			$this->query->whereIn( $this->foreign_key, $meta_values->unique()->all() );
		}
	}

	/**
	 * Retrieve the results of the query.
	 *
	 * @return \Mantle\Database\Model\Model|null
	 */
	public function get_results() {
		$this->add_constraints();

		return $this->query->first();
	}

	/**
	 * Associate a model with a relationship.
	 *
	 * @param Model $model Model to save to.
	 * @return Model
	 *
	 * @throws Model_Exception Thrown on error setting term for relationship.
	 */
	public function associate( Model $model ) {
		if ( ! $model->exists && $model instanceof Updatable ) {
			$model->save();
		}

		if ( ! $model instanceof Core_Object ) {
			throw new Model_Exception( 'Model must be an instance of Core_Object.' );
		}

		if ( ! $this->parent instanceof Core_Object ) {
			throw new Model_Exception( 'Parent model must be an instance of Core_Object.' );
		}

		if ( ! $this->parent instanceof Model_Meta ) {
			throw new Model_Exception( 'Parent model must be an instance of Model_Meta.' );
		}

		$append = Belongs_To_Many::class === static::class || is_subclass_of( $this, Belongs_To_Many::class );

		if ( $this->uses_terms ) {
			$set = wp_set_post_terms( $this->parent->id(), [ $this->get_term_for_relationship( $model ) ], static::RELATION_TAXONOMY, $append );

			if ( is_wp_error( $set ) ) {
				throw new Model_Exception( "Error associating term relationship for [{$this->parent->id()}]: [{$set->get_error_message()}]" );
			} elseif ( false === $set ) {
				throw new Model_Exception( "Unknown error associating term relationship for [{$this->parent->id()}]" );
			}
		} elseif ( $append ) {
			$this->parent->add_meta( $this->local_key, $model->id() );
		} else {
			$this->parent->set_meta( $this->local_key, $model->id() );
		}

		if ( $this->relationship ) {
			$this->parent->unset_relation( $this->relationship );
		}

		return $model;
	}

	/**
	 * Proxy to `Belongs_To::associate()`.
	 *
	 * @param Model $model Model to save to.
	 * @return Model
	 */
	public function save( Model $model ) {
		return $this->associate( $model );
	}

	/**
	 * Remove the relationship from the model.
	 *
	 * @throws Model_Exception Thrown when parent is not an instance of Core_Object.
	 * @throws Model_Exception Thrown when parent is not an instance of Model_Meta.
	 *
	 * @return static
	 */
	public function dissociate() {
		// todo: remove in PHP 8.1+.
		if ( ! $this->parent instanceof Core_Object ) {
			throw new Model_Exception( 'Parent model must be an instance of Core_Object.' );
		}

		if ( ! $this->parent instanceof Model_Meta ) {
			throw new Model_Exception( 'Parent model must be an instance of Model_Meta.' );
		}

		if ( $this->uses_terms ) {
			$term_ids = $this->get_term_ids_for_relationship( true );

			if ( ! empty( $term_ids ) ) {
				wp_remove_object_terms( $this->parent->id(), $term_ids, static::RELATION_TAXONOMY );
			}
		} else {
			$this->parent->delete_meta( $this->local_key );
		}

		if ( $this->relationship ) {
			$this->parent->unset_relation( $this->relationship );
		}

		return $this;
	}

	/**
	 * Add the query constraints for querying against the relationship.
	 *
	 * @param Builder     $builder Query builder instance.
	 * @param string|null $compare_value Value to compare against, optional.
	 * @param string      $compare Comparison operator (=, >, EXISTS, etc.).
	 *
	 * @throws Model_Exception Thrown on unsupported relationship method.
	 */
	public function get_relation_query( Builder $builder, ?string $compare_value = null, string $compare = 'EXISTS' ): Builder {
		if ( $this->uses_terms ) {
			throw new Model_Exception( 'Queries_Relationships does not support post <--> post relationships with terms.' );
		}

		if ( $compare_value ) {
			return $builder->whereMeta( $this->local_key, $compare_value, $compare );
		}

		return $builder->whereMeta( $this->local_key, '', $compare );
	}

	/**
	 * Retrieve a internal term for a post-to-post relationship.
	 *
	 * @param Model|string $model Model instance/id.
	 * @throws Model_Exception Thrown on error creating internal term with a post to term or term to post relationship.
	 */
	protected function get_term_for_relationship( $model ): int {
		$name = $this->get_term_slug_for_relationship( $model );
		$term = get_term_by( 'name', $name, static::RELATION_TAXONOMY );

		if ( empty( $term ) ) {
			$insert = wp_insert_term( $name, static::RELATION_TAXONOMY );

			if ( is_wp_error( $insert ) ) {
				throw new Model_Exception( "Error creating internal term for relationship: [{$insert->get_error_message()}]" );
			}

			return $insert['term_id'];
		}

		return $term->term_id;
	}

	/**
	 * Retrieve the term slug for a post-to-post relationship.
	 *
	 * @param Model|string $model Model instance/id.
	 */
	protected function get_term_slug_for_relationship( $model ): string {
		$delimiter = Has_One_Or_Many::DELIMITER;
		$model     = $model instanceof Model ? $model->get( $this->foreign_key ) : $model;

		return "{$this->local_key}{$delimiter}{$model}";
	}

	/**
	 * Retrieve term IDs from the current parent for the relationship.
	 *
	 * @throws Model_Exception Thrown when parent is not an instance of Core_Object.
	 *
	 * @param bool $return_term_ids Flag to return the term ID of the relationship
	 *                              term, parent ID otherwise.
	 * @return int[]|string[]
	 */
	protected function get_term_ids_for_relationship( bool $return_term_ids = false ): array {
		if ( ! $this->parent instanceof Core_Object ) {
			throw new Model_Exception( 'Parent model must be an instance of Core_Object.' );
		}

		$object_terms = get_the_terms( $this->parent->id(), static::RELATION_TAXONOMY );

		if ( empty( $object_terms ) || is_wp_error( $object_terms ) ) {
			return [];
		}

		return collect( $object_terms )
			->filter(
				function ( WP_Term $term ): bool {
					$key = Str::before_last( $term->slug, Has_One_Or_Many::DELIMITER );
					$id  = Str::after_last( $term->slug, Has_One_Or_Many::DELIMITER );

					return $key === $this->local_key && ! empty( $id );
				}
			)
			->map(
				function ( WP_Term $term ) use ( $return_term_ids ) {
					if ( $return_term_ids ) {
						return $term->term_id;
					}

					return (int) Str::after_last( $term->slug, Has_One_Or_Many::DELIMITER );
				}
			)
			->values()
			->to_array();
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param Collection $models Parent models.
	 * @param Collection $results Eagerly loaded results to match.
	 */
	public function match( Collection $models, Collection $results ): Collection {
		$dictionary = $this->build_dictionary( $results, $models );

		return $models->each(
			function ( $model ) use ( $dictionary ): void {
				$key = $model->meta->{$this->local_key};

				$model->set_relation( $this->relationship, $dictionary[ $key ][0] ?? null );
			}
		);
	}

	/**
	 * Build a model dictionary keyed by the relation's foreign key.
	 *
	 * @param Collection $results Collection of results.
	 * @param Collection $models Eagerly loaded results to match.
	 */
	protected function build_dictionary( Collection $results, Collection $models ): array {
		return $results
			->map_to_dictionary(
				fn ( $result ) => [ (string) $result[ $this->foreign_key ] => $result ]
			)
			->all();
	}

	/**
	 * Flag if the meta should appended.
	 */
	protected function should_append(): bool {
		return Belongs_To_Many::class === static::class || is_subclass_of( $this, Belongs_To_Many::class );
	}
}
