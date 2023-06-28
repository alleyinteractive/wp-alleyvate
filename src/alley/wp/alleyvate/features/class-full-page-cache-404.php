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
	 * Cache group.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'alleyvate';

	/**
	 * Cache key.
	 *
	 * @var string
	 */
	const CACHE_KEY = 'alleyvate_404_cache';

	/**
	 * Cache key for stale cache.
	 *
	 * @var string
	 */
	const STALE_CACHE_KEY = 'alleyvate_404_cache_stale';

	/**
	 * Cache time.
	 *
	 * @var int
	 */
	const CACHE_TIME = HOUR_IN_SECONDS;

	/**
	 * Stale cache time.
	 *
	 * @var int
	 */
	const STALE_CACHE_TIME = DAY_IN_SECONDS;

	/**
	 * Guaranteed 404 URI.
	 * Used for populating the cache.
	 */
	const GUARANTEED_404_URI = '/wp-alleyvate-this-is-a-404-page';

	/**
	 * Boot the feature.
	 */
	public function boot(): void {

		// Return 404 page cache on template_redirect.
		add_action( 'template_redirect', [ $this, 'action__template_redirect' ], 9999 );

		// Add HTTP header for debugging.
		add_action( 'send_headers', [ $this, 'action__send_headers' ] );

		// Force the Guaranteed 404 page to be a 404, because this is the page we will cache.
		add_action( 'pre_get_posts', [ $this, 'action__pre_get_posts' ] );

		// For the Guaranteed 404 page, hook in on WP to start output buffering, to capture the HTML.
		add_action( 'wp', [ $this, 'action__wp' ] );

		// Replenish the cache every hour.
		if ( ! wp_next_scheduled( 'alleyvate_404_cache' ) ) {
			wp_schedule_event( time(), 'hourly', 'alleyvate_404_cache' );
		}
		// Callback for Cron Event.
		add_action( 'alleyvate_404_cache', [ $this, 'trigger_404_page_cache' ] );
		add_action( 'alleyvate_404_cache_single', [ $this, 'trigger_404_page_cache' ] );
	}

	/**
	 * Get 404 Page Cache and return early if found.
	 */
	public function action__template_redirect() {

		// Allow 404s for the Admin.
		if ( is_admin() ) {
			return;
		}

		// Don't cache if not a 404.
		if ( ! is_404() ) {
			return;
		}

		// Allow this URL through, as this request will populate the cache.
		if ( isset( $_SERVER['REQUEST_URI'] ) && self::GUARANTEED_404_URI === $_SERVER['REQUEST_URI'] ) {
			return;
		}

		$cache = self::get_cache();

		if ( false === $cache ) {
			$cache = self::get_stale_cache();
		}
		if ( $cache ) {
			// Cached content is already escaped.
			echo $cache; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		} else {
			// Schedule a single event to generate the cache immediately.
			if ( ! wp_next_scheduled( 'alleyvate_404_cache_single' ) ) {
				wp_schedule_single_event( time(), 'alleyvate_404_cache_single' );
			}
			// If no cache, return an empty string.
			echo '';
			exit;
		}
	}

	/**
	 * Send Headers.
	 */
	public function action__send_headers() {
		if ( ! is_404() ) {
			return;
		}
		if ( isset( $_SERVER['REQUEST_URI'] ) && self::GUARANTEED_404_URI === $_SERVER['REQUEST_URI'] ) {
			return;
		}
		if ( self::get_cache() ) {
			header( 'X-Alleyvate-404-Cache: HIT' );
		} elseif ( self::get_stale_cache() ) {
			header( 'X-Alleyvate-404-Cache: HIT (stale)' );
		} else {
			header( 'X-Alleyvate-404-Cache: MISS' );
		}
	}

	/**
	 * Ensure that the 404 page is always a 404.
	 * We cache this page, so need to make sure it's always a 404.
	 *
	 * @param \WP_Query $query WP Query.
	 */
	public function action__pre_get_posts( $query ) {
		if ( isset( $_SERVER['REQUEST_URI'] ) && self::GUARANTEED_404_URI === $_SERVER['REQUEST_URI'] ) {
			global $wp_query;
			$wp_query->set_404();
		}
	}


	/**
	 * Start output buffering, so we can cache the 404 page.
	 */
	public function action__wp() {
		if ( isset( $_SERVER['REQUEST_URI'] ) && self::GUARANTEED_404_URI === $_SERVER['REQUEST_URI'] ) {
			ob_start( [ self::class, 'finish_output_buffering' ] );
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

	/**
	 * Get cache.
	 *
	 * @return mixed
	 */
	public function get_cache(): mixed {
		return wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Get stale cache.
	 *
	 * @return mixed
	 */
	public function get_stale_cache() {
		return wp_cache_get( self::STALE_CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Set cache.
	 *
	 * @param string $buffer The Output Buffer.
	 *
	 * @return void
	 */
	public function set_cache( string $buffer ): void {
		wp_cache_set( self::CACHE_KEY, $buffer, self::CACHE_GROUP, self::CACHE_TIME ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
		wp_cache_set( self::STALE_CACHE_KEY, $buffer, self::CACHE_GROUP, self::STALE_CACHE_TIME ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
	}

	/**
	 * Delete cache.
	 */
	public function delete_cache() {
		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
		wp_cache_delete( self::STALE_CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Populate cache.
	 */
	public function populate_cache() {
		ob_start( [ self::class, 'finish_output_buffering' ] );
	}

	/**
	 * Spin up a request to the guaranteed 404 page to populate the cache.
	 */
	public function trigger_404_page_cache() {
		$url = home_url( self::GUARANTEED_404_URI, 'https' );
		// replace http with https to ensure the styles don't get blocked due to insecure content.
		$url = str_replace( 'http://', 'https://', $url );

		// This request will populate the cache using output buffering.
		wpcom_vip_file_get_contents( $url );
	}
}
