<?php
/**
 * Comment_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Factory;

use Mantle\Database\Model\Comment;

use function Mantle\Support\Helpers\get_comment_object;

/**
 * Term Factory
 *
 * @template TModel of \Mantle\Database\Model\Comment
 * @template TObject of \WP_Comment
 * @template TReturnValue
 *
 * @extends Factory<TModel, TObject, TReturnValue>
 */
class Comment_Factory extends Factory {
	/**
	 * Model to use when creating objects.
	 *
	 * @var class-string
	 */
	protected string $model = Comment::class;

	/**
	 * Definition of the factory.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		return [
			'comment_author'     => $this->faker->name(),
			'comment_author_url' => $this->faker->url(),
			'comment_approved'   => 1,
			'comment_content'    => $this->faker->sentence(),
		];
	}

	/**
	 * Creates multiple comments on a given post.
	 *
	 * @param int   $post_id ID of the post to create comments for.
	 * @param int   $count   Total amount of comments to create.
	 * @param array $args    The comment details.
	 *
	 * @return int[] Array with the comment IDs.
	 */
	public function create_post_comments( int $post_id, int $count = 1, array $args = [] ) {
		$args['comment_post_ID'] = $post_id;
		return $this->create_many( $count, $args );
	}

	/**
	 * Retrieves an object by ID.
	 *
	 * @param int $object_id The object ID.
	 * @return \WP_Comment|null
	 */
	public function get_object_by_id( int $object_id ) {
		return get_comment_object( $object_id );
	}
}
