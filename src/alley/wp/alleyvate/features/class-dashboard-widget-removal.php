<?php
/**
 * Class file for Dashboard_Widget_Removal
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

/**
 * Disable selected unpopular dashboard widgets.
 */
final class Dashboard_Widget_Removal implements Feature {

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'wp_dashboard_setup', [ $this, 'dashboard_widget_removal' ] );
	}

	/**
	 * Disable selected unpopular dashboard widgets.
	 *
	 * @return void
	 */
	public function dashboard_widget_removal() {
		global $wp_meta_boxes;

		foreach ( $this->get_widgets as $widget ) {
			unset( $wp_meta_boxes['dashboard'][ $widget['context'] ][ $widget['priority'] ][ $widget['id'] ] );
		}
	}

	/**
	 * Getter for widgets to be removed.
	 *
	 * @return string[][]
	 */
	public function get_widgets() {
		return [
			[
				'context'  => 'side',
				'priority' => 'core',
				'id'       => 'dashboard_primary',
			],
			[
				'context'  => 'side',
				'priority' => 'core',
				'id'       => 'dashboard_quick_press',
			],
			[
				'context'  => 'side',
				'priority' => 'core',
				'id'       => 'jetpack_summary_widget',
			],
		];
	}
}
