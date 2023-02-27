<?php
/**
 * `load()` function
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate;

/**
 * Load plugin features.
 */
function load() {
	$features = [
		[
			'handle' => 'user_enumeration_restrictions',
			'path'   => __DIR__ . '/feature-user-enumeration-restrictions.php',
		],
	];

	foreach ( $features as $feature ) {
		$load = true;

		/**
		 * Filters whether to load an Alleyvate feature.
		 *
		 * @param bool   $load   Whether to load the feature. Default true.
		 * @param string $handle Feature handle.
		 */
		$load = apply_filters( 'alleyvate_load_feature', $load, $feature['handle'] );

		/**
		 * Filters whether to load the given Alleyvate feature.
		 *
		 * The dynamic portion of the hook name, `$handle`, refers to the
		 * machine name for the feature.
		 *
		 * @param bool $load Whether to load the feature. Default true.
		 */
		$load = apply_filters( "alleyvate_load_{$feature['handle']}", $load );

		if ( $load ) {
			// This file path is defined above.
			require_once $feature['path']; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		}
	}
}
