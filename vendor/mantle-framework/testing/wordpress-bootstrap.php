<?php // phpcs:disable
/**
 * This file installs and loads WordPress for running tests
 *
 * @package Mantle
 */

use Mantle\Testing\Utils;
use Mantle\Testing\WP_Die;

use function Mantle\Testing\tests_add_filter;

defined( 'MANTLE_IS_TESTING' ) || define( 'MANTLE_IS_TESTING', true );

require_once __DIR__ . '/class-utils.php';
require_once __DIR__ . '/class-wp-die.php';

// Ensure that Composer is loaded properly in the sub-process.
Utils::ensure_composer_loaded();

/*
 * Globalize some WordPress variables, because PHPUnit loads this file inside a function.
 * See: https://github.com/sebastianbergmann/phpunit/issues/325
 */
global $wpdb,
       $current_site,
       $current_blog,
       $wp_rewrite,
       $shortcode_tags,
       $wp,
       $table_prefix,
       $wp_theme_directories,
       $PHP_SELF;

// Load the configuration.
if ( defined( 'WP_TESTS_CONFIG_FILE_PATH' ) && ! empty( WP_TESTS_CONFIG_FILE_PATH ) && is_readable( WP_TESTS_CONFIG_FILE_PATH ) ) {
	$config_file_path = WP_TESTS_CONFIG_FILE_PATH;
} elseif ( false === strpos( __DIR__, '/wp-content/' ) ) {
	// Check if WP_CORE_DIR is defined and points to a valid installation.
	if ( getenv( 'WP_CORE_DIR' ) && ! defined( 'WP_TESTS_INSTALL_PATH' ) && is_readable( getenv( 'WP_CORE_DIR' ) . '/wp-load.php' ) ) {
		define( 'WP_TESTS_INSTALL_PATH', getenv( 'WP_CORE_DIR' ) );

		Utils::info( 'Using the WP_CORE_DIR environment variable: ' . WP_TESTS_INSTALL_PATH );

		$config_file_path = WP_TESTS_INSTALL_PATH . '/wp-tests-config.php';
	} else {
		/**
		 * Install WordPress automatically in the /tmp/wordpress folder.
		 *
		 * Retrieves the latest installation command from Mantle's GitHub and runs
		 * it to install WordPress to a temporary folder (WP_CORE_DIR if set falling
		 * back to /tmp/wordpress). This unlocks the ability to run `composer run
		 * phpunit` both locally and in CI tests.
		 */

		defined( 'WP_TESTS_INSTALL_PATH' ) || define( 'WP_TESTS_INSTALL_PATH', getenv( 'WP_CORE_DIR' ) ?: '/tmp/wordpress' );

		$config_file_path = WP_TESTS_INSTALL_PATH . '/wp-tests-config.php';

		// Install WordPress if we're not in the sub-process that installs WordPress.
		if ( ! defined( 'WP_INSTALLING' ) || ! WP_INSTALLING ) {
			Utils::info(
				'WordPress installation not found, installing in temporary directory: <em>' . WP_TESTS_INSTALL_PATH . '</em>'
			);

			// Download the latest installation command from GitHub and install WordPress.
			Utils::install_wordpress( WP_TESTS_INSTALL_PATH );
		}
	}
} else {
	// The project is being loaded from inside a WordPress installation.
	if ( defined( 'WP_TESTS_INSTALL_PATH' ) ) {
		$config_file_path = preg_replace( '#/wp-content/.*$#', '/wp-tests-config.php', (string) WP_TESTS_INSTALL_PATH );
	}

	if ( empty( $config_file_path ) ) {
		$config_file_path = preg_replace( '#/wp-content/.*$#', '/wp-tests-config.php', __DIR__ );
	}
}

if ( is_readable( $config_file_path ) ) {
	Utils::info( "Using configuration file: <em>{$config_file_path}</em>" );

	require_once $config_file_path;
} elseif ( ! defined( 'WP_INSTALLING' ) || ! WP_INSTALLING ) {
	Utils::info( 'No wp-tests-config.php file found, using default configuration.' );
}

Utils::setup_configuration();

// Attempt to load the vip-config.php file if it exists to play nicely with VIP Go.
if ( Utils::env_bool( 'MANTLE_LOAD_VIP_CONFIG', true ) ) {
	if ( file_exists( ABSPATH . '/wp-content/vip-config/vip-config.php' ) ) {
		require_once( ABSPATH . '/wp-content/vip-config/vip-config.php' );
	} elseif ( file_exists( ABSPATH . '/vip-config/vip-config.php' ) ) {
		require_once( ABSPATH . '/vip-config/vip-config.php' );
	}
}

Utils::reset_server();

define( 'WP_TESTS_TABLE_PREFIX', $table_prefix );
define( 'DIR_TESTDATA', __DIR__ . '/data' );

// Set the core test constant.
if ( ! defined( 'WP_RUN_CORE_TESTS' ) ) {
	define( 'WP_RUN_CORE_TESTS', true );
}

/*
 * Cron tries to make an HTTP request to the site, which always fails,
 * because tests are run in CLI mode only.
 */
define( 'DISABLE_WP_CRON', true );

define( 'WP_MEMORY_LIMIT', -1 );
define( 'WP_MAX_MEMORY_LIMIT', -1 );

// Disable VIP GO cache purging during testing.
if ( ! defined( 'VIP_GO_DISABLE_CACHE_PURGING' ) ) {
	define( 'VIP_GO_DISABLE_CACHE_PURGING', true );
}

$PHP_SELF            = '/index.php';
$GLOBALS['PHP_SELF'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// Should we run in multisite mode?
$multisite = ( '1' === getenv( 'WP_MULTISITE' ) );
$multisite = $multisite || ( defined( 'WP_TESTS_MULTISITE' ) && WP_TESTS_MULTISITE );
$multisite = $multisite || ( defined( 'MULTISITE' ) && MULTISITE );

// Replace the global phpmailer instance with a mock instance.
reset_phpmailer_instance();

// Include a WP_UnitTestCase class to allow for easier transition to the testing
// framework.
if ( ! Utils::env( 'DISABLE_WP_UNIT_TEST_CASE_SHIM', false ) ) {
	require_once __DIR__ . '/class-wp-unittestcase.php';
}

if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
	define( 'WP_DEFAULT_THEME', Utils::env( 'WP_DEFAULT_THEME', 'default' ) );
}

// Bail early if this is this is a parallel test bootstrap: installation of
// WordPress is handled in each process.
if ( Utils::is_parallel_bootstrap() ) {
	return;
}

$wp_theme_directories = [];
$installing_wp        = defined( 'WP_INSTALLING' ) && WP_INSTALLING;

if ( ! $installing_wp && '1' !== getenv( 'WP_TESTS_SKIP_INSTALL' ) ) {
	$resp = Utils::command(
		[
			WP_PHP_BINARY,
			escapeshellarg( __DIR__ . '/install-wordpress.php' ),
			$multisite,
		],
		$retval,
	);

	// Verify the return code and that 'Done!' is included in the output.
	if ( 0 !== $retval || empty( $resp ) || false === strpos( implode( ' ', $resp ), 'Done!' ) ) {
		Utils::error(
			'🚨 Error installing WordPress! Response from installation script:'
		);

		Utils::code( $resp );

		exit( $retval );
	} elseif ( Utils::is_debug_mode() ) {
		Utils::info( 'WordPress installation complete.' );
	}
}

// Ensure that the shutdown function is registered when installing WordPress.
if ( $installing_wp ) {
	Utils::register_shutdown_function();
}

if ( $multisite && ! $installing_wp ) {
	Utils::info( 'Running as multisite...' );
	defined( 'MULTISITE' ) or define( 'MULTISITE', true );
	defined( 'SUBDOMAIN_INSTALL' ) or define( 'SUBDOMAIN_INSTALL', false );
	$GLOBALS['base'] = '/';
} elseif ( ! $installing_wp ) {
	Utils::info( "Running as single site...\n<br>ℹ️ To run multisite, pass <span class=\"text-orange-500\">WP_MULTISITE=1</span> or set the <span class=\"text-orange-500\">WP_TESTS_MULTISITE=1</span> constant." );
}
unset( $multisite );

$GLOBALS['_wp_die_disabled'] = false;

// Allow tests to override wp_die().
tests_add_filter( 'wp_die_handler', [ WP_Die::class, 'get_toggled_handler' ] );

// Use the Spy REST Server instead of default.
tests_add_filter( 'wp_rest_server_class', [ Utils::class, 'wp_rest_server_class_filter' ], PHP_INT_MAX );

// Prevent updating translations asynchronously.
tests_add_filter( 'async_update_translation', '__return_false' );

// Disable background updates.
tests_add_filter( 'automatic_updater_disabled', '__return_true' );

// Disable VIP's alloptions protections during unit testing.
tests_add_filter( 'vip_mu_plugins_loaded', function (): void {
	remove_filter( 'pre_wp_load_alloptions', 'Automattic\\VIP\\Core\\OptionsAPI\\pre_wp_load_alloptions_protections', 999 );
} );

// Load WordPress.
require_once ABSPATH . '/wp-settings.php';

/*
 * See https://core.trac.wordpress.org/ticket/48605.
 */
if ( isset( $_SERVER['REQUEST_TIME'] ) ) {
	$_SERVER['REQUEST_TIME'] = (int) $_SERVER['REQUEST_TIME'];
}
if ( isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ) {
	$_SERVER['REQUEST_TIME_FLOAT'] = (float) $_SERVER['REQUEST_TIME_FLOAT'];
}

// Disable VIP's alloptions protections during unit testing.
remove_filter( 'pre_wp_load_alloptions', 'Automattic\\VIP\\Core\\OptionsAPI\\pre_wp_load_alloptions_protections', 999 );

// Delete any default posts & related data.
if ( is_blog_installed() ) {
	Utils::delete_all_posts();
}
