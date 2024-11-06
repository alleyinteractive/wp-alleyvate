<?php
/**
 * Plugin Name: wp-concurrent-remote-requests
 * Plugin URI: https://github.com/alleyinteractive/plugin_slug
 * Description: This is my plugin wp-concurrent-remote-requests
 * Version: 0.1.0
 * Author: Sean Fisher
 * Author URI: https://github.com/alleyinteractive/plugin_slug
 * Requires at least: 5.9
 * Tested up to: 5.9
 *
 * Text Domain: plugin_domain
 * Domain Path: /languages/
 *
 * @package package_name
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Composer is installed.
if ( ! file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	\add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Composer is not installed and the wp-concurrent-remote-requests cannot load. Try using a `*-built` branch if the plugin is being loaded as a submodule.', 'plugin_domain' ); ?></p>
			</div>
			<?php
		}
	);

	return;
}

// Load Composer dependencies.
require_once __DIR__ . '/vendor/wordpress-autoload.php';
