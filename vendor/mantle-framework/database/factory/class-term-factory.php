<?php
/**
 * Term_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory;

use Closure;
use Faker\Generator;
use Mantle\Database\Model\Term;

use function Mantle\Support\Helpers\get_term_object;
use function Mantle\Support\Helpers\tap;

/**
 * Term Factory
 *
 * @template TModel of \Mantle\Database\Model\Term
 * @template TObject of \WP_Term
 * @template TReturnValue
 *
 * @extends Factory<TModel, TObject, TReturnValue>
 */
class Term_Factory extends Factory {
	use Concerns\With_Meta;

	/**
	 * Model to use when creating objects.
	 *
	 * @var class-string<TModel>
	 */
	protected string $model = Term::class;

	/**
	 * Constructor.
	 *
	 * @param Generator $faker Faker generator.
	 * @param string    $taxonomy Taxonomy name.
	 */
	public function __construct( Generator $faker, protected string $taxonomy = 'post_tag' ) {
		parent::__construct( $faker );
	}

	/**
	 * Definition of the factory.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		return [
			'description' => trim( $this->faker->paragraph( 2 ) ),
			'name'        => $this->faker->words( wp_rand( 2, 4 ), true ),
			'taxonomy'    => $this->taxonomy,
		];
	}

	/**
	 * Create a new factory instance to create terms with a set of posts.
	 *
	 * Supports an array of post IDs or WP_Post objects.
	 *
	 * @param array<int, \WP_Post|int>|\WP_Post|int ...$posts Posts to assign to the term.
	 * @return static
	 */
	public function with_posts( ...$posts ) {
		$posts = collect( $posts )->flatten()->all();

		return $this->with_middleware(
			function ( array $args, Closure $next ) use ( $posts ) {
				/**
				 * The created term.
				 *
				 * @var \WP_Term $term
				 */
				$term = $next( $args );

				foreach ( $posts as $post ) {
					if ( $post instanceof \WP_Post ) {
						wp_set_object_terms( $post->ID, $term->term_id, $term->taxonomy, true );
					} else {
						wp_set_object_terms( $post, $term->term_id, $term->taxonomy, true );
					}
				}

				return $term;
			},
		);
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return \WP_Term|Term|null
	 */
	public function get_object_by_id( int $object_id ) {
		return $this->as_models
			? $this->model::find( $object_id )
			: get_term_object( $object_id );
	}

	/**
	 * Create a new factory instance to create posts for a specific taxonomy.
	 *
	 * @param string $taxonomy Post type to use.
	 */
	public function with_taxonomy( string $taxonomy ): static {
		return tap(
			clone $this,
			fn ( Term_Factory $factory ) => $factory->taxonomy = $taxonomy,
		);
	}

	/**
	 * Alias for {@see Term_Factory::with_taxonomy()}.
	 *
	 * @param string $taxonomy Taxonomy to use.
	 */
	public function for( string $taxonomy ): static {
		return $this->with_taxonomy( $taxonomy );
	}
}
