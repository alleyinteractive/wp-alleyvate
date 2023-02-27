<?php
/**
 * Plugin bootstrap
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP;

/*
 * Alleyvate features load after all plugins and themes have had a chance to add filters.
 * If Alleyvate is loaded before the WordPress plugin API loads, then preinitialize a
 * hook to run `load()`.
 */
if ( \function_exists( 'add_action' ) ) {
	add_action( 'after_setup_theme', __NAMESPACE__ . '\Alleyvate\load' );
} else {
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$GLOBALS['wp_filter']['after_setup_theme'][10][] = [
		'accepted_args' => 0,
		'function'      => __NAMESPACE__ . '\Alleyvate\load',
	];
}
