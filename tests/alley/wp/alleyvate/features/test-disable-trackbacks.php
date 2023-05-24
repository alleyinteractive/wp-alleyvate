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

use Alley\WP\Alleyvate\Feature;
use Mantle\Testkit\Test_Case;

/**
 * Tests for fully disabling pingback and trackback functionality.
 */
final class Test_Disable_Trackbacks extends Test_Case {
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
	 * Test that the feature removes support for trackbacks from post types.
	 */
	public function test_remove_trackback_support(): void {
		$post_id = self::factory()->post->create();

		// Ensure the "post" post type supports trackbacks out of the box.
		$this->assertTrue( post_type_supports( 'post', 'trackbacks' ) );

		// Ensure the ping status is reported as open out of the box.
		$result = rest_do_request( sprintf( '/wp/v2/posts/%d', $post_id ) );
		$this->assertSame( 'open', $result->data['ping_status'] );

		// Removing post type support happens on 'init', which has already occurred, so we need to call the callback directly.
		$this->feature::action__init();

		// Ensure the "post" post type no longer supports trackbacks after activating the feature.
		$this->assertFalse( post_type_supports( 'post', 'trackbacks' ) );

		// Ensure the ping status is reported as closed.
		$result = rest_do_request( sprintf( '/wp/v2/posts/%d', $post_id ) );
		$this->assertSame( 'closed', $result->data['ping_status'] );
	}
}
