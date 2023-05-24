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
	use \Mantle\Testing\Concerns\Admin_Screen;

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
	 * Tear down.
	 */
	protected function tearDown(): void {
		parent::tearDown();

		// Turn on comment flood checking again.
		remove_filter( 'wp_is_comment_flood', '__return_false', \PHP_INT_MAX );
	}

	/**
	 * Test that the feature prevents fetching a count of comments via the get_comments function.
	 */
	public function test_get_comments_count_returns_empty(): void {
		$post_id = self::factory()->post->create();
		wp_insert_comment(
			[
				'comment_post_ID' => $post_id,
				'comment_content' => 'Lorem ipsum dolor sit amet.',
			]
		);
		$this->assertSame(
			1,
			get_comments(
				[
					'post_id' => $post_id,
					'count'   => true,
				]
			)
		);

		// Activate the disable comments feature.
		$this->feature->boot();

		$this->assertSame(
			0,
			get_comments(
				[
					'post_id' => $post_id,
					'count'   => true,
				]
			)
		);
	}

	/**
	 * Test that the feature prevents posting new comments.
	 */
	public function test_prevent_comment_posting(): void {
		$post_id = self::factory()->post->create();

		// Turn off comment flood checking in order to run this test.
		add_filter( 'wp_is_comment_flood', '__return_false', \PHP_INT_MAX );

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
		$this->assertNotWPError( $result_pre );

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
		$this->assertWPError( $result_post );
	}

	/**
	 * Test that the Comments menu item is removed from the primary admin menu on the left and the admin bar.
	 */
	public function test_remove_comments_from_admin_menus(): void {
		// Reset admin menus.
		/* phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited */
		global $menu, $submenu;
		$menu    = [];
		$submenu = [];
		/* phpcs:enable */

		// Become admin and load the admin menu to build the $menu global.
		$this->acting_as( 'administrator' );
		require ABSPATH . 'wp-admin/includes/plugin.php';
		require ABSPATH . 'wp-admin/menu.php';

		// Ensure comments are in the menu before activating the feature.
		$this->assertNotEmpty( array_filter( $menu, fn( $item ) => 'edit-comments.php' === $item[2] ) );

		// Removing the menu item happens on 'admin_menu', which has already occurred, so we need to call the callback directly.
		$this->feature::action__admin_menu();

		// Ensure comments have been removed from the menu.
		$this->assertEmpty( array_filter( $menu, fn( $item ) => 'edit-comments.php' === $item[2] ) );

		// Build the admin bar menu and ensure comments are in it by default.
		$this->get( admin_url() );
		global $wp_admin_bar;
		do_action( 'admin_bar_menu', $wp_admin_bar ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$this->assertNotEmpty( $wp_admin_bar->get_node( 'comments' ) );

		// Removing the menu item happens on 'admin_bar_menu', which has already occurred, so we need to call the callback directly.
		$this->feature::action__admin_bar_menu( $wp_admin_bar );

		// Ensure the comments node was removed from the admin bar.
		$this->assertEmpty( $wp_admin_bar->get_node( 'comments' ) );
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
