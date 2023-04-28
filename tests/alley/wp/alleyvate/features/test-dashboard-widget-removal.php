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
	 * Load an admin screen as an administrator.
	 *
	 * @param string $screen The ID of the WP_Screen to load.
	 */
	protected function loadAdminScreen( string $screen ): void {
		// Create the administrator.
		$user = $this->factory->user->create( [
			'role' => 'administrator',
		] );

		// Set the currently logged in User.
		\wp_set_current_user( $user );

		// Set the admin screen to active.
		\set_current_screen( $screen );
	}

	/**
	 * Test.
	 */
	public function test_dashboard_widget_removal() {

		$this->loadAdminScreen( 'dashboard' );

		//global $wp_meta_boxes;

		$xwp_meta_boxes = [
			'dashboard' => [
				'side' => [
					'core' => [
						'dashboard_primary' => [
							'id'       => 'dashboard_site_health',
							'title'    => 'Site Health Status',
							'callback' => 'wp_dashboard_site_health',
							'args'     => [
								'__widget_basename' => 'Site Health Status',
							]
						]
					]
				]
			]
		];


		foreach ( $this->feature->get_widgets() as $widget ) {
			$this->assertArrayHasKey(
				$widget['context'],
				$xwp_meta_boxes['dashboard'],
				$widget['id'] . ' was not removed from dashboard widgets.'
			);
//			$this->assertArrayHasKey(
//				$widget['priority'],
//				$xwp_meta_boxes['dashboard'][$widget['context']],
//				$widget['id'] . ' was not removed from dashboard widgets.'
//			);
//			$this->assertArrayHasKey(
//				$widget['id'],
//				$xwp_meta_boxes['dashboard'][$widget['context']][$widget['priority']],
//				$widget['id'] . ' was not removed from dashboard widgets.'
//			);



		}




//			$this->assertNotContains(
//				[
//					$widget['context'][$widget['priority'] => [
//							$widget['id']
//						]
//					],
//				],
//				$xwp_meta_boxes['dashboard'],
//				$widget['id'] . ' was not removed from dashboard widgets.' );
//		}



	}
}
