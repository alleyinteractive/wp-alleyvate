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

/**
 * Plugin Name: Alleyvate
 * Plugin URI: https://github.com/alleyinteractive/wp-alleyvate
 * Description: Defaults for WordPress sites by Alley
 * Version: 2.0.0
 * Author: Alley
 * Author URI: https://www.alley.com
 * Requires at least: 6.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Load Composer dependencies.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} elseif ( ! class_exists( \Alley\WP\Alleyvate\Features\Redirect_Guess_Shortcircuit::class ) ) {
	// Bail if the Composer dependencies aren't loaded to prevent a fatal.
	return;
}

// Load the feature loader.
require_once __DIR__ . '/src/alley/wp/alleyvate/load.php';

// Alleyvate features load after all plugins and themes have had a chance to add filters.
add_action( 'after_setup_theme', 'Alley\WP\Alleyvate\load' );
