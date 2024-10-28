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
		add_action( 'template_include', [ self::class, 'disable_staff_archives' ], 999 );
		add_filter( 'get_the_author_display_name', [ self::class, 'filter__get_the_author_display_name' ], 999, 2 );
		add_filter( 'author_link', [ self::class, 'filter__author_link' ], 999, 2 );
	}

	/**
	 * Filters the author archive URL.
	 *
	 * @param string $link      The author link.
	 * @param int    $author_id The author ID.
	 * @return string
	 */
	public static function filter__author_link( $link, $author_id ): string {
		if ( ! is_singular() ) {
			return $link;
		}

		$author = get_user_by( 'ID', $author_id );
		if ( ! self::is_staff_author( $author->user_email ) ) {
			return $link;
		}

		return get_home_url();
	}

	/**
	 * Action fired once the post data has been set up. Passes the post and query by
	 * reference, so we can filter the objects directly.
	 *
	 * @param string $display_name The current post we are filtering.
	 * @param int    $author_id    The author ID.
	 * @return string
	 */
	public static function filter__get_the_author_display_name( $display_name, $author_id ): string {
		$author = get_user_by( 'ID', $author_id );

		if ( ! self::is_staff_author( $author->user_email ) ) {
			return $display_name;
		}

		return __( 'Staff', 'alley' );
	}

	/**
	 * Disable author archives for Alley--and other "general staff"--accounts.
	 *
	 * @param string $template The template currently being included.
	 * @return string The template to ultimately include.
	 */
	public static function disable_staff_archives( string $template ): string {
		global $wp_query;
		// If this isn't an author archive, skip it.
		if ( ! is_author() ) {
			return $template;
		}

		$author = $wp_query->get_queried_object();

		if ( ! self::is_staff_author( $author->user_email ) ) {
			return $template;
		}
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		return get_404_template();
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
				'local.test',
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
