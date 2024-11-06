<?php
/**
 * This file contains the Admin_Screen trait
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use WP_Screen;

/**
 * This trait, when used, sets the current screen so that `is_admin()` is true.
 */
trait Admin_Screen {
	/**
	 * Backed up screen.
	 *
	 * @var \WP_Screen|null
	 */
	protected $backup_screen;

	/**
	 * Backup the current screen.
	 */
	public function admin_screen_set_up(): void {
		/** WordPress Administration Screen API */
		if ( ! class_exists( 'WP_Screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		}
		
		if ( ! function_exists( 'get_current_screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}

		// phpcs:disable WordPress.WP.GlobalVariablesOverride
		$GLOBALS['pagenow']      = 'index.php';
		$GLOBALS['wp_importers'] = null;
		$GLOBALS['hook_suffix']  = 'index.php';
		$GLOBALS['plugin_page']  = null;
		$GLOBALS['typenow']      = '';
		$GLOBALS['taxnow']       = '';
		// phpcs:enable

		$this->backup_screen = get_current_screen();
		set_current_screen( 'dashboard-user' );
	}

	/**
	 * Restore the backed up screen.
	 */
	public function admin_screen_tear_down(): void {
		// Restore current_screen.
		set_current_screen( $this->backup_screen );
		unset(
			$GLOBALS['pagenow'],
			$GLOBALS['wp_importers'],
			$GLOBALS['hook_suffix'],
			$GLOBALS['plugin_page'],
			$GLOBALS['typenow'],
			$GLOBALS['taxnow']
		);
	}
}
