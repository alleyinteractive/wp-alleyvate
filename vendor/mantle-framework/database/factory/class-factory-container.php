<?php
/**
 * Factory_Container class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory;

use Faker\Generator;
use InvalidArgumentException;
use Mantle\Contracts\Container;
use Mantle\Faker\Faker_Provider;
use Mantle\Support\Traits\Macroable;

/**
 * Collect all the Database Factories for IDE Support
 *
 * Allows IDEs to type-hint the individual factories and their child methods.
 *
 * This method is used in unit testing primarily to mirror core's testing
 * factories.
 */
class Factory_Container {
	use Macroable;

	/**
	 * Attachment Factory
	 *
	 * @var Attachment_Factory<\Mantle\Database\Model\Attachment, \WP_Post, \WP_Post>
	 */
	public Attachment_Factory $attachment;

	/**
	 * Blog Factory
	 *
	 * @var Blog_Factory<\Mantle\Database\Model\Site, \WP_Site, \WP_Site>
	 */
	public Blog_Factory $blog;

	/**
	 * Category Factory
	 *
	 * @var Term_Factory<\Mantle\Database\Model\Term, \WP_Term, \WP_Term>
	 */
	public Term_Factory $category;

	/**
	 * Comment Factory
	 *
	 * @var Comment_Factory<\Mantle\Database\Model\Comment, \WP_Comment, \WP_Comment>
	 */
	public Comment_Factory $comment;

	/**
	 * Network Factory
	 *
	 * @var Network_Factory<null, \WP_Network>
	 */
	public Network_Factory $network;

	/**
	 * Page Factory
	 *
	 * @var Post_Factory<\Mantle\Database\Model\Post, \WP_Post, \WP_Post>
	 */
	public $page;

	/**
	 * Post Factory
	 *
	 * @var Post_Factory<\Mantle\Database\Model\Post, \WP_Post, \WP_Post>
	 */
	public Post_Factory $post;

	/**
	 * Tag Factory
	 *
	 * @var Term_Factory<\Mantle\Database\Model\Term, \WP_Term, \WP_Term>
	 */
	public Term_Factory $tag;

	/**
	 * Term Factory (alias for Tag Factory).
	 *
	 * @var Term_Factory<\Mantle\Database\Model\Term, \WP_Term, \WP_Term>
	 */
	public Term_Factory $term;

	/**
	 * User Factory
	 *
	 * @var User_Factory<\Mantle\Database\Model\User, \WP_User, \WP_User>
	 */
	public User_Factory $user;

	/**
	 * Constructor.
	 *
	 * @param Container $container Container instance.
	 */
	public function __construct( Container $container ) {
		$this->setup_faker( $container );

		$this->attachment = $container->make( Attachment_Factory::class );
		$this->category   = $container->make( Term_Factory::class, [ 'taxonomy' => 'category' ] );
		$this->comment    = $container->make( Comment_Factory::class );
		$this->page       = $container->make( Post_Factory::class, [ 'post_type' => 'page' ] );
		$this->post       = $container->make( Post_Factory::class );
		$this->tag        = $container->make( Term_Factory::class, [ 'taxonomy' => 'post_tag' ] );
		$this->term       = $container->make( Term_Factory::class, [ 'taxonomy' => 'post_tag' ] );
		$this->user       = $container->make( User_Factory::class );

		if ( is_multisite() ) {
			$this->blog    = $container->make( Blog_Factory::class );
			$this->network = $container->make( Network_Factory::class );
		}
	}

	/**
	 * Set up the Faker instance in the container.
	 *
	 * Primarily used when faker/factory is called from a data provider and the
	 * application hasn't been setup yet.
	 *
	 * @param Container $container Container instance.
	 */
	protected function setup_faker( Container $container ): void {
		$container->singleton_if(
			Generator::class,
			function () {
				$generator = \Faker\Factory::create();

				$generator->unique();

				$generator->addProvider( new Faker_Provider( $generator ) );

				return $generator;
			},
		);
	}

	/**
	 * Magic method to retrieve a custom post type/taxonomy factory.
	 *
	 * @throws InvalidArgumentException If the post type or taxonomy does not exist.
	 * @throws InvalidArgumentException If both post type and taxonomy exist.
	 *
	 * @param string $name Factory name.
	 * @return Post_Factory|Term_Factory Factory instance.
	 */
	public function __get( string $name ) {
		if ( post_type_exists( $name ) && taxonomy_exists( $name ) ) {
			throw new InvalidArgumentException( "Error creating dynamic factory for {$name}. Both post type and taxonomy exist." );
		}

		if ( post_type_exists( $name ) ) {
			return $this->post->with_post_type( $name );
		}

		if ( taxonomy_exists( $name ) ) {
			return $this->term->with_taxonomy( $name );
		}

		throw new InvalidArgumentException( "Error creating dynamic factory for {$name}. Post type or taxonomy does not exist." );
	}
}
