<?php
/**
 * Installs_WordPress trait file
 *
 * @package Mantle
 */

namespace Mantle\Testkit\Concerns;

/**
 * Concern for handling WordPress installation outside of the bootstrap.
 */
trait Installs_WordPress {
	/**
	 * Manages installing WordPress.
	 *
	 * This is useful for installing WordPress in integration tests, while leaving
	 * the bootstrap free of this overhead during unit tests. To perform actions
	 * after WordPress is installed,  you can use the Installation Manager to
	 * define a callback to be invoked before/after installation.
	 */
	public static function installs_wordpress_set_up_before_class(): void {
		static $installed = false;

		if ( true === $installed ) {
			return;
		}

		/**
		 * Fire a callback to a named function called 'mantle_after_wordpress_install'.
		 *
		 * @deprecated Logic condensed into {@see \Mantle\Testing\Installation_Manager::after()}.
		 */
		$callback = function_exists( 'mantle_after_wordpress_install' ) ? 'mantle_after_wordpress_install' : null;

		\Mantle\Testing\manager()
			->after( $callback )
			->install();

		$installed = true;
	}
}
