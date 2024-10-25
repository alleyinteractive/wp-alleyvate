<?php
/**
 * Class file for Disable_Alley_Authors
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Types\Feature;
use WP_Query;
use WP_User;
use WP_User_Query;

/**
 * Removes the impact of Alley accounts on the frontend of client websites by:
 *  - Ensuring Alley users do not have author archive pages.
 *  - Ensuring Byline Manager and Co-Authors Plus profiles linked to Alley users do not have author archive pages.
 *  - Filtering Alley account usernames to display as "Staff" on the frontend.
 */
final class Disable_Alley_Authors implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
	}

	/**
	 * Generate an array of authors in the database that are to be defined as "Staff"
	 * authors and not attributable authors.
	 *
	 * @return int[]
	 */
	public static function get_staff_authors(): array {
		return self::generate_wp_query_for_domains( [
			'alley.com',
			'alley.co',
		] );
	}

	/**
	 * Accepts an array of email domains and generates a custom SQL query of the Users
	 * database similar to WP_User_Query, which only accepts a single search string at
	 * time.
	 *
	 * @param string[] $domains The array of domain names to search for.
	 * @return array
	 */
	protected static function generate_wp_query_for_domains( array $domains ): array {
		global $wpdb;

		$domains = array_map( fn ( $domain ) => trim( $domain, '@' ), $domains );

		$columns = [ 'ID', 'user_email' ];

		$fields = [];
		foreach ( $columns as $field ) {
			$field    = 'id' === $field ? 'ID' : sanitize_key( $field );
			$fields[] = "$wpdb->users.$field"; // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users -- Usage in this instance is used for a performance gain over multiple queries.
		}

		$fields = implode( ', ', $fields );
		$where  = 'WHERE 1=1';

		$domain_groups = [];
		foreach ( $domains as $domain ) {
			$domain_groups[] = $wpdb->prepare(
				'user_email LIKE %s',
				'%' . $wpdb->esc_like( $domain ),
			);
		}

		if ( count( $domain_groups ) > 0 ) {
			$where .= ' AND (' . implode( ' OR ', $domain_groups ) . ')';
		}

		$found       = false;
		$pre_results = wp_cache_get( sha1( $where ), 'alleyvate_authors_search', false, $found );

		if ( $found ) {
			return $pre_results;
		}

		/*
		 * Usage of WP_Query_User would be preferable here, but it doesn't allow for an "or" relationship.
		 * To do this ourselves, we've re-created the same structure of query, but limited to our requirements.
		 */
		$result = $wpdb->get_results( "SELECT {$fields} FROM {$wpdb->users} $where" ); // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		wp_cache_set( sha1( $where ), $result, 'alleyvate_authors_search', 1800 );

		return $result;
	}
}
