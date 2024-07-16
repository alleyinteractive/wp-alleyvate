<?php
/**
 * Class file for Test_Disable_Sticky_Posts
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

declare( strict_types=1 );

namespace Alley\WP\Alleyvate\Features;

use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testkit\Test_Case;

/**
 * Tests for fully disabling sticky posts.
 */
final class Test_Disable_Sticky_Posts extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature to test.
	 *
	 * @var Disable_Sticky_Posts
	 */
	protected Disable_Sticky_Posts $feature;

	/**
	 * Setup the test case.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disable_Sticky_Posts();
	}

	/**
	 * Test that sticky posts are disabled in queries on the homepage.
	 */
	public function test_disable_sticky_posts_in_query(): void {
		$posts         = self::factory()->post->create_ordered_set( 5 );
		$stick_post_id = self::factory()->post->create(
			[
				'post_date' => '2019-01-01 00:00:00',
			]
		);

		stick_post( $stick_post_id );

		$this->assertTrue( is_sticky( $stick_post_id ) );

		// Make a request to the homepage and inspect the query.
		$this->get( '/' );

		$this->assertCount( 6, $GLOBALS['wp_query']->posts );
		$this->assertEquals( $stick_post_id, $GLOBALS['wp_query']->posts[0]->ID );

		// Activate the disable sticky post feature.
		$this->feature->boot();

		// The post should no longer be considered sticky.
		$this->assertFalse( is_sticky( $stick_post_id ) );

		// Make another request to the homepage and inspect the query.
		$this->get( '/' );

		$this->assertCount( 6, $GLOBALS['wp_query']->posts );
		$this->assertNotEquals( $stick_post_id, $GLOBALS['wp_query']->posts[0]->ID );

		// Ensure that the post order is correct (i.e. the sticky post is at the
		// end and the ordered set is in the proper order after it).
		$this->assertEquals(
			[
				...array_reverse( $posts ),
				$stick_post_id,
			],
			array_column( $GLOBALS['wp_query']->posts, 'ID' ),
		);
	}

	/**
	 * Test that sticky posts are disabled in Gutenberg.
	 */
	public function test_disable_action_sticky_rest_api_edit(): void {
		$this->acting_as( 'administrator' );

		$post_id = self::factory()->post->create();

		$this->get( rest_url( 'wp/v2/posts/' . $post_id . '?context=edit' ) )
			->assertJsonPathExists( '_links.wp:action-sticky' );

		// Activate the disable sticky post feature.
		$this->feature->boot();

		$this->get( rest_url( 'wp/v2/posts/' . $post_id . '?context=edit' ) )
			->assertJsonPathMissing( '_links.wp:action-sticky' );
	}
}
