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

use Alley\WP\Alleyvate\Feature;

/**
 * Cleans admin bar.
 */
final class Clean_Admin_Bar implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'wp_before_admin_bar_render', [ $this, 'before_admin_bar_render' ], 9999 );
	}

	/**
	 * Set menus to be disabled.
	 *
	 * @return mixed|void
	 */
	public function menus() {
		$default_menus = [
			'comments',
			'customize',
			'themes',
			'wp-seo-menu', // Yoast.
		];

		return apply_filters( 'alleyvate_clean_admin_bar_menus', $default_menus );
	}

	/**
	 * Disables specified menus in admin bar.
	 *
	 * @return void
	 */
	public function before_admin_bar_render() {
		global $wp_admin_bar;
		foreach ( $this->menus() as $menu ) {
			$wp_admin_bar->remove_menu( $menu );
		}
	}
}
