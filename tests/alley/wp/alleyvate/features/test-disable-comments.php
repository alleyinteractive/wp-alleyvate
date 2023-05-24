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

		// Turn off comment flood checking in order to run these tests.
		add_filter( 'wp_is_comment_flood', '__return_false', \PHP_INT_MAX );

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
	 * Test that the feature removes rewrite rules related to comments.
	 */
	public function test_remove_rewrite_rules(): void {
		// Ensure comments rewrite rules exist before activating the feature.
		$rewrite_rules = get_option( 'rewrite_rules' );
		$this->assertSame(
			'index.php?&feed=$matches[1]&withcomments=1',
			$rewrite_rules['comments/feed/(feed|rdf|rss|rss2|atom)/?$']
		);
		$this->assertSame(
			'index.php?&feed=$matches[1]&withcomments=1',
			$rewrite_rules['comments/(feed|rdf|rss|rss2|atom)/?$']
		);
		$this->assertSame(
			'index.php?&embed=true',
			$rewrite_rules['comments/embed/?$']
		);
		$this->assertSame(
			'index.php?attachment=$matches[1]&cpage=$matches[2]',
			$rewrite_rules['[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$']
		);
		$this->assertSame(
			'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&cpage=$matches[5]',
			$rewrite_rules['([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/comment-page-([0-9]{1,})/?$']
		);
		$this->assertSame(
			'index.php?attachment=$matches[1]&cpage=$matches[2]',
			$rewrite_rules['[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$']
		);
		$this->assertSame(
			'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&cpage=$matches[4]',
			$rewrite_rules['([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/comment-page-([0-9]{1,})/?$']
		);
		$this->assertSame(
			'index.php?year=$matches[1]&monthnum=$matches[2]&cpage=$matches[3]',
			$rewrite_rules['([0-9]{4})/([0-9]{1,2})/comment-page-([0-9]{1,})/?$']
		);
		$this->assertSame(
			'index.php?year=$matches[1]&cpage=$matches[2]',
			$rewrite_rules['([0-9]{4})/comment-page-([0-9]{1,})/?$']
		);
		$this->assertSame(
			'index.php?attachment=$matches[1]&cpage=$matches[2]',
			$rewrite_rules['.?.+?/attachment/([^/]+)/comment-page-([0-9]{1,})/?$']
		);
		$this->assertSame(
			'index.php?pagename=$matches[1]&cpage=$matches[2]',
			$rewrite_rules['(.?.+?)/comment-page-([0-9]{1,})/?$']
		);

		// Activate feature.
		$this->feature->boot();

		// Flush the rewrite rules and load in our changes.
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		$rewrite_rules = get_option( 'rewrite_rules' );

		// Ensure rewrite rules have been removed.
		$this->assertArrayNotHasKey( 'comments/feed/(feed|rdf|rss|rss2|atom)/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( 'comments/(feed|rdf|rss|rss2|atom)/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( 'comments/embed/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/comment-page-([0-9]{1,})/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/comment-page-([0-9]{1,})/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '([0-9]{4})/([0-9]{1,2})/comment-page-([0-9]{1,})/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '([0-9]{4})/comment-page-([0-9]{1,})/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '.?.+?/attachment/([^/]+)/comment-page-([0-9]{1,})/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '(.?.+?)/comment-page-([0-9]{1,})/?$', $rewrite_rules );
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

	/**
	 * Test that the feature reports comment count as 0.
	 */
	public function test_suppress_comments_number(): void {
		// Create a post and give it a comment.
		$post_id = self::factory()->post->create();
		wp_insert_comment(
			[
				'comment_post_ID' => $post_id,
				'comment_content' => 'Lorem ipsum dolor sit amet.',
			]
		);

		// Ensure that the get_comments_number function returns the correct comment count.
		$this->assertSame( '1', get_comments_number( $post_id ) );

		// Activate the feature.
		$this->feature->boot();

		// Ensure the comments number is reported as 0.
		$this->assertSame( 0, get_comments_number( $post_id ) );
	}
}
