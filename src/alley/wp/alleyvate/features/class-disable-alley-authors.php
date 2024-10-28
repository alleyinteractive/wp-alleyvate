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
	 * @param string $email The email address to compare against.
	 * @return int[]
	 */
	public static function is_staff_author( string $email ): bool {
		/**
		 * Filters which domains to use for defining staff users in the user database.
		 *
		 * @param string[]  $domains The array of domains. Defaults to alley domains.
		 */
		$domains = apply_filters(
			'alleyvate_staff_author_domains',
			[
				'alley.com',
				'alley.co',
			]
		);

		$domains = array_map(
			/**
			 * Force domains to take the format of an email domain, to avoid
			 * false positives like `alley.com@example.com` which is a valid
			 * email.
			 *
			 * @param string $domain The domain to filter.
			 * @return string The filtered domain value.
			 */
			function ( $domain ) {
				if ( trim( $domain, '@' ) !== $domain ) {
					return $domain;
				}

				return '@' . $domain;
			},
			$domains
		);

		return (bool) array_reduce(
			$domains,
			function ( $carry, $domain ) use ( $email ) {
				if ( ! $carry ) {
					$carry = str_contains( $email, $domain );
				}
				return $carry;
			},
			false
		);
	}
}
