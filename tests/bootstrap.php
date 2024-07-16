<?php
/**
 * PHPUnit bootstrap
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

/*
 * This is required to override `wp_get_environment_type()`. Otherwise
 * the method caches the initial result and always returns it, even if
 * we modify the environment variable.
 */
define( 'WP_RUN_CORE_TESTS', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound

\Mantle\Testing\manager()
	// Fires on 'muplugins_loaded'.
	->loaded(
		function () {
			require_once __DIR__ . '/../wp-alleyvate.php';

			/*
			 * Turn off all features by default so that we can verify that the behavior
			 * of WordPress changes after we turn the feature on.
			 */
			add_filter( 'alleyvate_load_feature', '__return_false' );
		},
	)
	->install();
