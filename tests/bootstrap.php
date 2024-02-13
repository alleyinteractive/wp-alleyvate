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

define( 'MANTLE_REQUIRE_OBJECT_CACHE', true );

\Mantle\Testing\manager()
	// Fires on 'muplugins_loaded'.
	->with_object_cache()
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
