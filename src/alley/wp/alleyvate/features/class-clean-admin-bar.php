<?php
/**
 * Class file for Clean_Admin_Bar
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
 * Cleans admin bar.
 */
final class Clean_Admin_Bar implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'wp_before_admin_bar_render', [ $this, 'before_admin_bar_render' ], 9999 );
	}

	/**
	 * Disables specified menus in admin bar.
	 */
	public function before_admin_bar_render(): void {
		global $wp_admin_bar;

		foreach ( $this->get_disposable_nodes() as $node ) {
			$wp_admin_bar->remove_menu( $node );
		}
	}

	/**
	 * Set menus to be disabled.
	 *
	 * @return array<string>
	 */
	public function get_disposable_nodes(): array {
		$disposable_nodes = [
			'comments',
			'themes',
			'updates',
			'wp-logo',
		];

		/**
		 * Filters the admin bar menus to be removed.
		 *
		 * @param array<string> $disposable_nodes Admin bar menus to be removed.
		 */
		return (array) apply_filters( 'alleyvate_clean_admin_bar_menus', $disposable_nodes );
	}
}
