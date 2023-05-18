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
	 * Test that the comment_status field on the post object is coerced into always being closed.
	 */
	public function test_feature_comments_always_closed() {
		$post = self::factory()->post->create_and_get();

		// Test to ensure that comments are on by default.
		$this->assertSame( 'open', $post->comment_status );

		// Activate the disable comments feature.
		$this->feature->boot();

		// Test to ensure that comments are coerced to be off.
		$this->assertSame( 'closed', $post->comment_status );
	}

	/**
	 * Test that the feature disables comments when active.
	 */
	public function test_feature_disable_comments() {
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
}
