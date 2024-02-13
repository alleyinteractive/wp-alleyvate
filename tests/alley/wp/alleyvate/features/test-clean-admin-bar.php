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

declare( strict_types=1 );

namespace Alley\WP\Alleyvate\Features;

use WP_Admin_Bar;
use Mantle\Testkit\Test_Case;
use Mantle\Testing\Concerns\Refresh_Database;

/**
 * Tests for the cleaning of the admin bar.
 */
final class Test_Clean_Admin_Bar extends Test_Case {
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Clean_Admin_Bar
	 */
	private Clean_Admin_Bar $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Clean_Admin_Bar();
	}

	/**
	 * Test default admin bar cleaning.
	 */
	public function test_remove_admin_bar_nodes(): void {
		$admin_bar = $this->apply_admin_bar();

		// Get nodes to compare.
		$disposable_nodes = $this->feature->get_disposable_nodes();
		$current_nodes    = $admin_bar->get_nodes();

		// Let's make sure the nodes exist before we remove them.
		foreach ( $disposable_nodes as $disposable_node ) {
			// Updates will not exist in a test context.
			if ( 'updates' === $disposable_node ) {
				continue;
			}
			$this->assertArrayHasKey( $disposable_node, $current_nodes, $disposable_node . ' should exist in $wp_admin_bar global prior to boot.' );
		}

		// Boot feature.
		$this->feature->boot();
		do_action( 'wp_before_admin_bar_render' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		// Get updated set of nodes.
		$current_nodes = $admin_bar->get_nodes();

		// Compare again.
		foreach ( $disposable_nodes as $disposable_node ) {
			$this->assertArrayNotHasKey( $disposable_node, $current_nodes, $disposable_node . ' should not exist in $wp_admin_bar global after boot.' );
		}
	}

	/**
	 * Test admin bar cleaning using filter.
	 */
	public function test_filter(): void {
		$admin_bar = $this->apply_admin_bar();
		$node      = 'my-account';

		add_filter(
			'alleyvate_clean_admin_bar_menus',
			function ( $disposable_nodes ) use ( $node ) {
				$disposable_nodes[] = $node;

				return $disposable_nodes;
			}
		);

		// Get nodes to compare.
		$current_nodes = $admin_bar->get_nodes();

		// Let's make sure the node exists before we remove it.
		$this->assertArrayHasKey( $node, $current_nodes, 'The filtered node ' . $node . ' should exist in $wp_admin_bar global prior to boot.' );

		// Boot feature.
		$this->feature->boot();
		do_action( 'wp_before_admin_bar_render' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		// Get updated set of nodes.
		$current_nodes = $admin_bar->get_nodes();

		$this->assertArrayNotHasKey( $node, $current_nodes, 'The filtered node ' . $node . ' should not exist in $wp_admin_bar global after boot.' );
	}

	/**
	 * Apply the admin bar.
	 *
	 * @global WP_Admin_Bar $wp_admin_bar Core class used to implement the Toolbar API.
	 *
	 * @return WP_Admin_Bar
	 */
	public function apply_admin_bar(): WP_Admin_Bar {
		// Load file required to work with the admin bar.
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';

		// Set role.
		$this->acting_as( 'administrator' );

		// Make admin bar go.
		global $wp_admin_bar;
		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', [ &$wp_admin_bar ] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		return $wp_admin_bar;
	}
}
