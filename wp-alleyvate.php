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
 * Version: 3.7.0
 * Author: Alley
 * Author URI: https://www.alley.com
 * Requires at least: 6.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Load Composer dependencies.
if ( file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	require_once __DIR__ . '/vendor/wordpress-autoload.php';
}

// Load the feature loader.
require_once __DIR__ . '/src/alley/wp/alleyvate/load.php';

\Alley\WP\Alleyvate\load();
