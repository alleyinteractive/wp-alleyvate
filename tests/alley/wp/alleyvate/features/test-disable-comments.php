<?php
/**
 * Class file for Test_Disable_Comments
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;
use Mantle\Testkit\Test_Case;

/**
 * Tests for fully disabling comment functionality.
 */
final class Test_Disable_Comments extends Test_Case {
	/**
	 * Feature instance.
	 *
	 * @var Feature
	 */
	private Feature $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disable_Comments();
	}

	/**
	 * Test that the feature prevents posting new comments.
	 */
	public function test_prevent_comment_posting(): void {
		$post_id = self::factory()->post->create();

		// Turn off comment flood checking in order to run this test.
		add_filter( 'wp_is_comment_flood', '__return_false', PHP_INT_MAX );

		// Post a comment on the post and ensure that it posts correctly.
		$result_pre = wp_handle_comment_submission(
			[
				'author'          => 'Test Author',
				'comment'         => 'Lorem ipsum dolor sit amet.',
				'comment_parent'  => 0,
				'comment_post_ID' => $post_id,
				'email'           => 'user@example.org',
				'url'             => 'https://example.org',
			]
		);
		$this->assertFalse( is_wp_error( $result_pre ) );

		// Activate the disable comments feature.
		$this->feature->boot();

		// Try again, and this time it should fail to insert.
		$result_post = wp_handle_comment_submission(
			[
				'author'          => 'Testy McTesterson',
				'comment'         => 'A new comment on a post with comments closed.',
				'comment_parent'  => 0,
				'comment_post_ID' => $post_id,
				'email'           => 'testy@example.org',
				'url'             => 'https://example.org/testy',
			]
		);
		$this->assertTrue( is_wp_error( $result_post ) );

		// Turn on comment flood checking again.
		remove_filter( 'wp_is_comment_flood', '__return_false', PHP_INT_MAX );
	}

	/**
	 * Test that the feature removes post type support for comments.
	 */
	public function test_remove_post_type_support(): void {
		// Ensure that the default is to enable comments on the 'post' post type.
		$this->assertTrue( post_type_supports( 'post', 'comments' ) );

		// Removing post type support happens on 'init', which has already occurred, so we need to call the callback directly.
		$this->feature::action__init();

		// Ensure that the 'post' post type no longer supports comments.
		$this->assertFalse( post_type_supports( 'post', 'comments' ) );
	}

	/**
	 * Test that the feature suppresses being able to fetch comments for posts altogether.
	 */
	public function test_suppress_comment_fetch(): void {
		// Make a post and give it a comment, then ensure the comment is returned.
		$post_id = self::factory()->post->create();
		wp_insert_comment(
			[
				'comment_post_ID' => $post_id,
				'comment_content' => 'Lorem ipsum dolor sit amet.',
			]
		);
		$this->assertNotEmpty( get_comments( [ 'post_id' => $post_id ] ) );

		// Activate the disable comments feature.
		$this->feature->boot();

		// Ensure comments are suppressed.
		$this->assertEmpty( get_comments( [ 'post_id' => $post_id ] ) );
	}
}
