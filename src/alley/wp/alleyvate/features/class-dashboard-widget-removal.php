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
	 * Array of widgets to be removed.
	 *
	 * @var array|\string[][]
	 */
	public array $widgets = [
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

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'wp_dashboard_setup', [ $this, 'remove_dashboard_widgets' ] );
	}

	/**
	 * Disable selected unpopular dashboard widgets.
	 *
	 * @return void
	 */
	public function remove_dashboard_widgets() {
		foreach ( $this->widgets as $widget ) {
			remove_meta_box( $widget['id'], 'dashboard', $widget['context'] );
		}
	}

}
