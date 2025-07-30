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
use WP_REST_Response;

/**
 * Removes the impact of Alley accounts on the frontend of client websites by:
 *  - Ensuring Alley users do not have author archive pages.
 *  - Ensuring Byline Manager and Co-Authors Plus profiles linked to Alley users do not have author archive pages.
 *  - Filtering Alley account usernames to display as "Staff" on the frontend.
 */
final class Disable_Alley_Authors implements Feature {

	/**
	 * Add an early hook to decide if this feature should load or not, based on the environment.
	 */
	public function __construct() {
		add_filter( 'alleyvate_load_disable_alley_authors_in_environment', [ self::class, 'restrict_to_environment' ], 999, 2 );
	}

	/**
	 * Accepts whether or not the feature should load, as well as the current environment,
	 * to allow for disabling this feature on certain environments.
	 *
	 * @param bool   $load        Whether or not to load the feature.
	 * @param string $environment The loaded environment.
	 * @return bool
	 */
	public static function restrict_to_environment( $load, $environment ): bool {
		if ( ! $load ) {
			return $load;
		}

		$allowed_environments = apply_filters( 'alleyvate_disable_alley_authors_environments', [ 'production' ] );

		return in_array( $environment, $allowed_environments, true );
	}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'template_include', [ self::class, 'filter__template_include' ], 999 );
		add_filter( 'get_the_author_display_name', [ self::class, 'filter__get_the_author_display_name' ], 999, 2 );
		add_filter( 'render_block_core/post-author', [ self::class, 'filter__render_block_post_author' ], 999 );
		add_filter( 'author_link', [ self::class, 'filter__author_link' ], 999, 2 );
		add_filter( 'get_coauthors', [ self::class, 'filter__get_coauthors' ], 999 );
	}

	/**
	 * Filter out any staff CoAuthors from the get_coauthors request.
	 *
	 * @param array $coauthors The array of coauthors.
	 * @return array
	 */
	public static function filter__get_coauthors( $coauthors ) {
		if ( ! is_array( $coauthors ) ) {
			return $coauthors;
		}

		$staff_found = false;
		foreach ( $coauthors as $key => $author ) {
			if ( ! self::is_staff_author( $author->user_email ) ) {
				continue;
			}

			// If we've found staff already, remove this record instead of changing it.
			if ( $staff_found ) {
				unset( $coauthors[ $key ] );
				continue;
			}

			// Set CoAuthors data to staff information.
			$coauthors[ $key ] = (object) [
				'ID'             => 0,
				'display_name'   => 'Staff',
				'first_name'     => '',
				'last_name'      => '',
				'user_login'     => '',
				'user_email'     => '',
				'linked_account' => '',
				'website'        => '',
				'user_nicename'  => 'staff',
				'type'           => 'guest-author',
				'nickname'       => '',
			];

			// We now have a staff record, so we can skip all future ones.
			$staff_found = true;
		}

		// Use array_values here to make sure the array keys are set correctly.
		return array_values( $coauthors );
	}

	/**
	 * Filter the render block for core/post-author when Byline Manager is used.
	 *
	 * @param string $block The parsed block.
	 * @return string
	 */
	public static function filter__render_block_post_author( $block ): string {

		if ( ! class_exists( '\Byline_Manager\Models\Profile' ) ) {
			return (string) $block;
		}

		$post        = get_post();
		$byline_meta = get_post_meta( $post->ID, 'byline', true );

		if ( empty( $byline_meta ) || ! is_array( $byline_meta ) ) {
			return $block;
		}

		$original_bylines = explode( PHP_EOL, $block );
		$bylines          = [];
		foreach ( $byline_meta['profiles'] as $profile ) {
			if ( 'text' === $profile['type'] ) {
				$bylines[] = $profile['atts']['text'];
				continue;
			}

			$byline      = \Byline_Manager\Models\Profile::get_by_post( $profile['atts']['post_id'] );
			$user_id     = $byline->get_linked_user_id();
			$linked_user = get_user_by( 'ID', $user_id );

			if ( false === $linked_user || ! self::is_staff_author( $linked_user->user_email ) ) {
				$bylines[] = $byline->display_name;
				continue;
			}

			if ( ! in_array( 'Staff', $bylines, true ) ) {
				$bylines[] = 'Staff';
			}
		}

		$new_block              = [];
		$staff_template         = null;
		$staff_name_placeholder = null;
		foreach ( $original_bylines as $original_byline ) {
			foreach ( $bylines as $byline ) {
				if ( ! empty( $byline ) && strpos( $original_byline, $byline ) !== false && ! in_array( $original_byline, $new_block, true ) ) {
					$new_block[] = $original_byline;
					if ( empty( $staff_name_placeholder ) || empty( $staff_template ) ) {
						$staff_name_placeholder = $byline;
						$staff_template         = $original_byline;
					}
				}
			}
		}

		if ( count( $new_block ) < count( $original_bylines ) ) {
			$new_block[] = str_replace( $staff_name_placeholder, 'Staff', $staff_template );
		}

		return implode( PHP_EOL, $new_block );
	}

	/**
	 * Filters the author archive URL.
	 *
	 * @param string $link      The author link.
	 * @param int    $author_id The author ID.
	 * @return string
	 */
	public static function filter__author_link( $link, $author_id ): string {
		global $coauthors_plus;

		if ( ! is_singular() ) {
			return $link;
		}

		$author = get_user_by( 'ID', $author_id );

		if ( false === $author && $coauthors_plus instanceof \CoAuthors_Plus ) {
			$author = $coauthors_plus->get_coauthor_by( 'id', $author_id );
		}

		if ( false !== $author && ! self::is_staff_author( $author->user_email ) ) {
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
	public static function filter__template_include( string $template ): string {
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
