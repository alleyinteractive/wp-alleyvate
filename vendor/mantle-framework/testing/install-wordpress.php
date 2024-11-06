<?php // phpcs:disable
/**
 * Installs WordPress for the purpose of the unit-tests
 *
 * @todo Reuse the init/load code in init.php
 */

use Mantle\Testing\Utils;

error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );

define( 'WP_INSTALLING', true );

$PHP_SELF            = '/index.php';
$GLOBALS['PHP_SELF'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

global $wp_rewrite;

require_once __DIR__ . '/preload.php';
require_once __DIR__ . '/wordpress-bootstrap.php';
require_once ABSPATH . '/wp-admin/includes/upgrade.php';

if ( file_exists( ABSPATH . '/wp-includes/class-wpdb.php' ) ) {
	require_once ABSPATH . '/wp-includes/class-wpdb.php';
} else {
	// Back-compat for WordPress < 6.1.0.
	require_once ABSPATH . '/wp-includes/wp-db.php';
}

$multisite = ! empty( $argv[1] );

$wpdb->query( 'SET default_storage_engine = InnoDB' );
$wpdb->select( DB_NAME, $wpdb->dbh );

echo 'Installing WordPress...' . PHP_EOL;

$wpdb->query( 'SET foreign_key_checks = 0' );
foreach ( $wpdb->tables() as $table => $prefixed_table ) {
	//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS $prefixed_table" );
}

foreach ( $wpdb->tables( 'ms_global' ) as $table => $prefixed_table ) {
	//phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS $prefixed_table" );

	// We need to create references to ms global tables.
	if ( $multisite ) {
		$wpdb->$table = $prefixed_table;
	}
}
$wpdb->query( 'SET foreign_key_checks = 1' );

// Prefill a permalink structure so that WP doesn't try to determine one itself.
add_action( 'populate_options', [ Utils::class, 'set_default_permalink_structure_for_tests' ] );

wp_install( WP_TESTS_TITLE, 'admin', WP_TESTS_EMAIL, true, null, 'password' );

// Delete dummy permalink structure, as prefilled above.
if ( ! is_multisite() ) {
	delete_option( 'permalink_structure' );
}
remove_action( 'populate_options', [ Utils::class, 'set_default_permalink_structure_for_tests' ] );

if ( $multisite ) {
	echo '... Installing network...' . PHP_EOL;

	define( 'WP_INSTALLING_NETWORK', true );

	$title             = WP_TESTS_TITLE . ' Network';
	$subdomain_install = false;

	install_network();

	$populate = populate_network( 1, WP_TESTS_DOMAIN, WP_TESTS_EMAIL, $title, '/', $subdomain_install );

	if ( is_wp_error( $populate ) ) {
		echo 'Error populating network: ' . $populate->get_error_message() . PHP_EOL;
		exit( 1 );
	}

	$wp_rewrite->set_permalink_structure( '' );
}

echo "... Done!\n";
