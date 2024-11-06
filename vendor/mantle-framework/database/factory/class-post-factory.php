<?php
/**
 * Post_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory;

use Carbon\Carbon;
use Closure;
use Faker\Generator;
use Mantle\Database\Model\Post;
use WP_Post;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\get_post_object;
use function Mantle\Support\Helpers\tap;

/**
 * Post Factory
 *
 * @template TModel of \Mantle\Database\Model\Post
 * @template TObject
 * @template TReturnValue
 *
 * @extends Factory<TModel, TObject, TReturnValue>
 */
class Post_Factory extends Factory {
	use Concerns\With_Meta;

	/**
	 * Model to use when creating objects.
	 *
	 * @var class-string<TModel>
	 */
	protected string $model = Post::class;

	/**
	 * Constructor.
	 *
	 * @param Generator $faker Faker generator.
	 * @param string    $post_type Post type to use.
	 */
	public function __construct( Generator $faker, public string $post_type = 'post' ) {
		parent::__construct( $faker );
	}

	/**
	 * Create a new factory instance to create posts with a set of terms.
	 *
	 * @param array<int|string, \WP_Term|int|string|array<string, mixed>>|\WP_Term|int|string ...$terms Terms to assign to the post.
	 */
	public function with_terms( ...$terms ): static {
		// Handle an array in the first argument.
		if ( 1 === count( $terms ) && is_array( $terms[0] ) ) {
			$terms = $terms[0];
		}

		$terms = collect( $terms )->all();

		return $this->with_middleware(
			fn ( array $args, Closure $next ) => $next( $args )->set_terms( $terms ),
		);
	}

	/**
	 * Attach a post thumbnail to the post.
	 *
	 * Note: the underlying attachment does not actually exist for performance.
	 * You can use `with_real_thumbnail()` to create a real underlying attachment
	 * for the post thumbnail.
	 */
	public function with_thumbnail(): static {
		return $this->with_meta(
			[
				'_thumbnail_id' => ( new Attachment_Factory( $this->faker ) )->create(),
			]
		);
	}

	/**
	 * Attach a thumbnail to the post with an underlying file attachment.
	 *
	 * @param string $file   The file name to create attachment object from.
	 * @param int    $width  The width of the image.
	 * @param int    $height The height of the image.
	 * @param bool   $recycle Whether to recycle the image file.
	 */
	public function with_real_thumbnail( string $file = null, int $width = 640, int $height = 480, bool $recycle = true ): static {
		return $this->with_middleware(
			function ( array $args, Closure $next ) use ( $file, $width, $height, $recycle ) {
				$post = $next( $args );

				update_post_meta(
					$post->ID,
					'_thumbnail_id',
					( new Attachment_Factory( $this->faker ) )->with_image(
						file: $file,
						width: $width,
						parent: $post->ID,
						height: $height,
						recycle: $recycle
					)->create(),
				);

				return $post;
			}
		);
	}

	/**
	 * Create a new factory instance to create posts for a specific post type.
	 *
	 * @param string $post_type Post type to use.
	 */
	public function with_post_type( string $post_type ): static {
		return tap(
			clone $this,
			fn ( Post_Factory $factory ) => $factory->post_type = $post_type,
		);
	}

	/**
	 * Alias for {@see Post_Factory::with_post_type()}.
	 *
	 * @param string $post_type Post type to use.
	 */
	public function for( string $post_type ): static {
		return $this->with_post_type( $post_type );
	}

	/**
	 * Definition of the factory.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		return [
			'post_content' => trim( (string) $this->faker->paragraph_blocks( 3 ) ),
			'post_excerpt' => trim( $this->faker->paragraph( 2 ) ),
			'post_status'  => 'publish',
			'post_title'   => $this->faker->sentence(),
			'post_type'    => $this->post_type,
		];
	}

	/**
	 * Create a post with a thumbnail.
	 *
	 * @deprecated Use {@see Post_Factory::with_thumbnail()} instead.
	 *
	 * @param array $args The arguments.
	 */
	public function create_with_thumbnail( array $args = [] ): ?int {
		return $this->with_thumbnail()->create( $args );
	}

	/**
	 * Create an ordered set of posts.
	 *
	 * Useful to create posts in a specific order for testing. Creates posts in
	 * chronological order separated by a defined number of seconds, the
	 * default of which is equal to 1 hour.
	 *
	 * @param int           $count The number of posts to create.
	 * @param array         $args The arguments.
	 * @param Carbon|string $starting_date The starting date for the posts, defaults to
	 *                                     a month ago.
	 * @param int           $separation The number of seconds between each post.
	 * @return array<int, int>
	 */
	public function create_ordered_set(
		int $count = 10,
		array $args = [],
		$starting_date = null,
		int $separation = 3600
	): array {
		if ( ! ( $starting_date instanceof Carbon ) ) {
			$starting_date = $starting_date
				? Carbon::parse( $starting_date )
				: Carbon::now()->subMonth();
		}

		// Set the date for the first post (seconds added before each run).
		$date = $starting_date->subSeconds( $separation );

		return collect()
			->pad( $count, null )
			->map(
				fn () => $this->create(
					array_merge(
						$args,
						[
							'date' => $date->addSeconds( $separation )->format( 'Y-m-d H:i:s' ),
						]
					)
				)
			)
			->to_array();
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return Post|WP_Post|null
	 * @phpstan-return TModel|TObject|null
	 */
	public function get_object_by_id( int $object_id ) {
		return $this->as_models
			? $this->model::find( $object_id )
			: get_post_object( $object_id );
	}
}
