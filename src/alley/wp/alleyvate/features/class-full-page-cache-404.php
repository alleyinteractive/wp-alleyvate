<?php
/**
 * Class file for Full Page Cache for 404s
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;

/**
 * Full Page Cache for 404s.
 */
final class Full_Page_Cache_404 implements Feature {

	/**
	 * Cache key.
	 *
	 * @var string
	 */
	const CACHE_KEY = 'alleyvate_404_cache';

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'alleyvate';

	/**
	 * Cache time.
	 *
	 * @var int
	 */
	const CACHE_TIME = HOUR_IN_SECONDS;

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'wp', [ $this, 'action__wp' ], 9999 );
	}

	/**
	 * Get 404 Page Cache and return early if found.
	 *
	 * @param \WP $wp WP object.
	 */
	public function action__wp( \WP $wp ) {

		// Don't cache admin pages.
		if ( is_admin() ) {
			return;
		}

		// Don't cache if not a 404.
		if ( ! is_404() ) {
			return;
		}

		$cache = self::get_cache();

		if ( $cache ) {
			header( 'X-Alleyvate-404-Cache: HIT' );
			// Cached content is already escaped.
			echo $cache; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		} else {
			/**
			 * Avoid generating cache during logged in requests, incase 404 page is customized
			 * or contains private posts.
			 */
			if ( $this->should_cache_404_request() ) {
				header( 'X-Alleyvate-404-Cache: MISS' );
				// Start output buffering so that this 404 request can be cached.
				ob_start( [ self::class, 'finish_output_buffering' ] );
			}
		}
	}

	/**
	 * Finish output buffering.
	 *
	 * @param string $buffer Buffer.
	 */
	public function finish_output_buffering( $buffer ) {
		global $wp_query;
		if ( ! $wp_query->is_404() ) {
			return $buffer;
		}
		if ( ! $this->get_cache() ) {
			self::set_cache( $buffer );
		}
		return $buffer;
	}

	private function should_cache_404_request() {
		return ! is_user_logged_in() && ! is_admin();
	}

	/**
	 * Get cache.
	 *
	 * @return mixed
	 */
	public function get_cache(): mixed {
		return wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Set cache.
	 *
	 * @param string $buffer The Output Buffer.
	 *
	 * @return void
	 */
	public function set_cache( $buffer ): void {
		wp_cache_set( self::CACHE_KEY, $buffer, self::CACHE_GROUP, self::CACHE_TIME ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
	}

	/**
	 * Delete cache.
	 *
	 */
	public function delete_cache() {
		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
	}

}
