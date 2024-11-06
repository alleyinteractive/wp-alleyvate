<?php
/**
 * This file contains the Refresh_Database trait
 *
 * @package Mantle
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users

namespace Mantle\Testing\Concerns;

trait Refresh_Database {

	/**
	 * Routines to run before setupBeforeClass.
	 */
	public static function refresh_database_setup_before_class(): void {
		global $wpdb;

		$wpdb->suppress_errors = false;
		$wpdb->show_errors     = true;
		$wpdb->db_connect();

		ini_set( 'display_errors', '1' ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed
	}

	/**
	 * Start the transaction on setUp().
	 */
	public function refresh_database_set_up(): void {
		$this->start_transaction();
	}

	/**
	 * Routines to run on tearDown().
	 */
	public function refresh_database_tear_down(): void {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
		remove_filter( 'query', [ $this, 'create_temporary_tables' ] );
		remove_filter( 'query', [ $this, 'drop_temporary_tables' ] );
	}

	/**
	 * Commit the queries in a transaction.
	 */
	public static function commit_transaction(): void {
		global $wpdb;
		$wpdb->query( 'COMMIT;' );
	}

	/**
	 * Starts a database transaction.
	 */
	public function start_transaction(): void {
		global $wpdb;
		$wpdb->query( 'SET autocommit = 0;' );
		$wpdb->query( 'START TRANSACTION;' );
		add_filter( 'query', [ $this, 'create_temporary_tables' ] );
		add_filter( 'query', [ $this, 'drop_temporary_tables' ] );
	}

	/**
	 * Replaces the `CREATE TABLE` statement with a `CREATE TEMPORARY TABLE` statement.
	 *
	 * @param string $query The query to replace the statement for.
	 * @return string The altered query.
	 */
	public function create_temporary_tables( $query ) {
		if ( str_starts_with( trim( $query ), 'CREATE TABLE' ) ) {
			return substr_replace( trim( $query ), 'CREATE TEMPORARY TABLE', 0, 12 );
		}
		return $query;
	}

	/**
	 * Replaces the `DROP TABLE` statement with a `DROP TEMPORARY TABLE` statement.
	 *
	 * @param string $query The query to replace the statement for.
	 * @return string The altered query.
	 */
	public function drop_temporary_tables( $query ) {
		if ( str_starts_with( trim( $query ), 'DROP TABLE' ) ) {
			return substr_replace( trim( $query ), 'DROP TEMPORARY TABLE', 0, 10 );
		}
		return $query;
	}
}
// phpcs:enable
