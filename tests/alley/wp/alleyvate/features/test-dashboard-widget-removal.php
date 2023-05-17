<?php
/**
 * Class file for Test_Dashboard_Widget_Removal
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
use Mantle\Testing\Concerns\Admin_Screen;

/**
 * Tests for disabling selected unpopular dashboard widgets.
 */
final class Test_Dashboard_Widget_Removal extends Test_Case {

	use Admin_Screen;

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

		$this->feature = new Dashboard_Widget_Removal();
	}

	/**
	 * Test that widgets have been removed.
	 */
	public function test_remove_dashboard_widgets() {

		// Load files required to get wp_meta_boxes global.
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';

		$this->feature->boot();
		\set_current_screen( 'dashboard' );
		\wp_dashboard_setup();

		global $wp_meta_boxes;

		$array_keys = $this->array_keys_r( $wp_meta_boxes );

		foreach ( $this->feature->get_widgets() as $widget ) {
			$this->assertNotContains(
				$widget['id'],
				$array_keys,
				$widget['id'] . ' was not removed from dashboard widgets.'
			);
		}
	}

	/**
	 * Helper function for getting all array keys, recursively.
	 *
	 * @param array $array Array to recursively parse.
	 *
	 * @return array
	 */
	protected function array_keys_r( array $array ): array {
		$keys = array_keys( $array );
		foreach ( $array as $i ) {
			if ( is_array( $i ) ) {
				$keys = array_merge( $keys, $this->array_keys_r( $i ) );
			}
		}

		return $keys;
	}
}
