<?php
/**
 * Class file for Test_Disable_Dashboard_Widgets
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

use Mantle\Testing\Concerns\{Admin_Screen, Refresh_Database};
use Mantle\Testkit\Test_Case;

/**
 * Tests for disabling selected unpopular dashboard widgets.
 */
final class Test_Disable_Dashboard_Widgets extends Test_Case {
	use Admin_Screen;
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Disable_Dashboard_Widgets
	 */
	private Disable_Dashboard_Widgets $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->feature = new Disable_Dashboard_Widgets();

		$this->prevent_stray_requests();
		$this->fake_request();
	}

	/**
	 * Test that widgets have been removed.
	 */
	public function test_action__disable_dashboard_widgets(): void {

		// Load files required to get wp_meta_boxes global.
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';

		$this->acting_as( 'administrator' );
		set_current_screen( 'dashboard' );
		wp_dashboard_setup();
		global $wp_meta_boxes;

		foreach ( $this->feature->widgets as $widget ) {
			if ( str_contains( $widget['id'], 'jetpack' ) ) {
				continue;
			}
			$this->assertNotEmpty(
				$wp_meta_boxes['dashboard'][ $widget['context'] ][ $widget['priority'] ][ $widget['id'] ],
				$widget['id'] . ' was not in dashboard widgets.'
			);
		}

		$this->feature->boot();
		// Reset the dashboard post boot.
		wp_dashboard_setup();

		foreach ( $this->feature->widgets as $widget ) {
			$this->assertEmpty(
				$wp_meta_boxes['dashboard'][ $widget['context'] ][ $widget['priority'] ][ $widget['id'] ],
				$widget['id'] . ' was not removed from dashboard widgets.'
			);
		}
	}
}
