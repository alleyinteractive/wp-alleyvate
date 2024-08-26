<?php
/**
 * Class file for Test_Disable_Trackbacks
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testkit\Test_Case;

/**
 * Tests for fully disabling pingback and trackback functionality.
 */
final class Test_Disable_Trackbacks extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Disable_Trackbacks
	 */
	private Disable_Trackbacks $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disable_Trackbacks();
	}

	/**
	 * Test that the feature prevents adding trackbacks.
	 */
	public function test_prevent_adding_trackbacks(): void {
		$post_id = self::factory()->post->create();

		// Ensure pings are open by default.
		$this->assertTrue( pings_open( $post_id ) );

		// Activate the feature.
		$this->feature->boot();

		// Ensure pings are turned off by the plugin.
		$this->assertFalse( pings_open( $post_id ) );
	}

	/**
	 * Test that the feature removes rewrite rules related to trackbacks.
	 */
	public function test_remove_rewrite_rules(): void {
		// Ensure trackback rewrite rules exist before activating the feature.
		$rewrite_rules = get_option( 'rewrite_rules' );
		$this->assertArrayHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/trackback/?$', $rewrite_rules );
		$this->assertArrayHasKey( '([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/trackback/?$', $rewrite_rules );
		$this->assertArrayHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/trackback/?$', $rewrite_rules );
		$this->assertArrayHasKey( '.?.+?/attachment/([^/]+)/trackback/?$', $rewrite_rules );
		$this->assertArrayHasKey( '(.?.+?)/trackback/?$', $rewrite_rules );

		// Activate feature.
		$this->feature->boot();

		// Flush the rewrite rules and load in our changes.
		flush_rewrite_rules( false ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		$rewrite_rules = get_option( 'rewrite_rules' );

		// Ensure rewrite rules have been removed.
		$this->assertArrayNotHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/trackback/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/trackback/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/trackback/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '.?.+?/attachment/([^/]+)/trackback/?$', $rewrite_rules );
		$this->assertArrayNotHasKey( '(.?.+?)/trackback/?$', $rewrite_rules );
	}

	/**
	 * Test that the feature removes support for trackbacks from post types.
	 */
	public function test_remove_trackback_support(): void {
		$post_id = self::factory()->post->create();

		// Ensure the "post" post type supports trackbacks out of the box.
		$this->assertTrue( post_type_supports( 'post', 'trackbacks' ) );

		// Ensure the ping status is reported as open out of the box.
		$result = rest_do_request( \sprintf( '/wp/v2/posts/%d', $post_id ) );
		$this->assertSame( 'open', $result->data['ping_status'] );

		// Removing post type support happens on 'init', which has already occurred, so we need to call the callback directly.
		$this->feature::action__init();

		// Ensure the "post" post type no longer supports trackbacks after activating the feature.
		$this->assertFalse( post_type_supports( 'post', 'trackbacks' ) );

		// Ensure the ping status is reported as closed.
		$result = rest_do_request( \sprintf( '/wp/v2/posts/%d', $post_id ) );
		$this->assertSame( 'closed', $result->data['ping_status'] );
	}
}
