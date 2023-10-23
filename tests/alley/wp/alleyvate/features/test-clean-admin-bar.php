<?php
/**
 * Class file for Test_Clean_Admin_Bar
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
 * Tests for the cleaning of the admin bar.
 */
final class Test_Clean_Admin_Bar extends Test_Case {
	use \Mantle\Testing\Concerns\Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Feature
	 */
	private Feature $feature;

	/**
	 * Test that the feature disallows file editing.
	 */
	public function test_clean_admin_bar() {

		// Load file required to work with the admin bar.
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';

		// Set role.
		$this->acting_as( 'administrator' );

		// Make admin bar go.
		global $wp_admin_bar;
		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', [ &$wp_admin_bar ] );

		// Get nodes to compare.
		$disposable_nodes = $this->feature->get_disposable_nodes();
		$current_nodes    = $wp_admin_bar->get_nodes();

		// Let's make sure they are there before we remove them.
		foreach ( $disposable_nodes as $disposable_node ) {
			$this->assertArrayHasKey( $disposable_node, $current_nodes, $disposable_node . ' should exist in $wp_admin_bar prior to boot.' );
		}

		// Boot feature.
		$this->feature->boot();
		do_action( 'wp_before_admin_bar_render' );

		// Get updated set of nodes.
		$current_nodes    = $wp_admin_bar->get_nodes();

		// Compare again.
		foreach ( $disposable_nodes as $disposable_node ) {
			$this->assertArrayNotHasKey( $disposable_node, $current_nodes, $disposable_node . ' should not exist in $wp_admin_bar after boot.' );
		}

	}

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Clean_Admin_Bar();
	}
}
