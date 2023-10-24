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
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Clean_Admin_Bar();
	}

	/**
	 * Test default admin bar cleaning.
	 */
	public function test_remove_admin_bar_nodes() {

		$admin_bar = $this->apply_admin_bar();

		// Get nodes to compare.
		$disposable_nodes = $this->feature->get_disposable_nodes();
		$current_nodes    = $admin_bar->get_nodes();

		// Let's make sure they are there before we remove them.
		foreach ( $disposable_nodes as $disposable_node ) {
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
	public function test_filter() {

		$admin_bar = $this->apply_admin_bar();

		$node = 'comments';
		add_filter(
			'alleyvate_clean_admin_bar_menus',
			function ( $disposable_nodes ) use ( $node ) {
				$disposable_nodes[] = $node;

				return $disposable_nodes;
			}
		);

		// Boot feature.
		$this->feature->boot();
		do_action( 'wp_before_admin_bar_render' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		// Get updated set of nodes.
		$current_nodes = $admin_bar->get_nodes();

		$this->assertArrayNotHasKey( $node, $current_nodes, 'The filtered node ' . $node . ' should not exist in $wp_admin_bar global after boot.' );

	}

	/**
	 * Apply the admin bar.
	 */
	public function apply_admin_bar() {
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


