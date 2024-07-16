<?php
/**
 * Class file for Disable_Dashboard_Widgets
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Types\Feature;

/**
 * Disable selected unpopular dashboard widgets.
 */
final class Disable_Dashboard_Widgets implements Feature {

	/**
	 * Array of widgets to be removed.
	 *
	 * @var array<array{context: string, priority: string, id: string}>
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
		add_action( 'wp_dashboard_setup', [ $this, 'action__disable_dashboard_widgets' ] );
	}

	/**
	 * Disable selected unpopular dashboard widgets.
	 *
	 * @return void
	 */
	public function action__disable_dashboard_widgets() {
		foreach ( $this->widgets as $widget ) {
			remove_meta_box( $widget['id'], 'dashboard', $widget['context'] );
		}
	}
}
