<?php
/**
 * Class file for Full Page Cache for 404s.
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

declare( strict_types=1 );

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Types\Feature;

/**
 * Full Page Cache for 404s.
 */
final class Full_Page_Cache_404 implements Feature {

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	public const CACHE_GROUP = 'alleyvate';

	/**
	 * Cache key.
	 *
	 * @var string
	 */
	public const CACHE_KEY = '404_cache';

	/**
	 * Cache key for stale cache.
	 *
	 * @var string
	 */
	public const STALE_CACHE_KEY = '404_cache_stale';

	/**
	 * Cache time.
	 *
	 * @var int
	 */
	public const CACHE_TIME = HOUR_IN_SECONDS;

	/**
	 * Stale cache time.
	 *
	 * @var int
	 */
	public const STALE_CACHE_TIME = DAY_IN_SECONDS;

	/**
	 * Guaranteed 404 URI.
	 * Used for populating the cache.
	 *
	 * @var string
	 */
	public const TEMPLATE_GENERATOR_URI = '/wp-alleyvate/404-template-generator/?generate=1&uri=1';

	/**
	 * Boot the feature.
	 */
	public function boot(): void {

		/**
		 * Only boot feature if external object cache is being used.
		 *
		 * We don't want to store the cached 404 page in the database.
		 */
		if ( ! (bool) wp_using_ext_object_cache() ) {
			return;
		}

		// Return 404 page cache on template_redirect.
		add_action( 'template_redirect', [ self::class, 'action__template_redirect' ], 1 );

		// For the Guaranteed 404 page, hook in on WP to start output buffering, to capture the HTML.
		add_action( 'wp', [ self::class, 'action__wp' ] );

		// Replenish the cache every hour.
		if ( ! wp_next_scheduled( 'alleyvate_404_cache' ) ) {
			wp_schedule_event( time(), 'hourly', 'alleyvate_404_cache' );
		}

		// Callback for Cron Event.
		add_action( 'alleyvate_404_cache', [ self::class, 'trigger_404_page_cache' ] );
		add_action( 'alleyvate_404_cache_single', [ self::class, 'trigger_404_page_cache' ] );
	}

	/**
	 * Get 404 Page Cache and return early if found.
	 */
	public static function action__template_redirect(): void {

		// Don't cache if user is logged in.
		if ( is_user_logged_in() ) {
			return;
		}

		// Don't cache if not a 404.
		if ( ! is_404() ) {
			return;
		}

		// Allow this URL through, as this request will populate the cache.
		if ( isset( $_SERVER['REQUEST_URI'] ) && self::TEMPLATE_GENERATOR_URI === $_SERVER['REQUEST_URI'] ) {
			return;
		}

		echo self::get_cached_response_with_headers(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( \defined( 'MANTLE_IS_TESTING' ) && MANTLE_IS_TESTING ) {
			wp_die( '', '', [ 'response' => 404 ] );
		}

		exit;
	}

	/**
	 * Get cached response with headers.
	 *
	 * @return string
	 */
	public static function get_cached_response_with_headers(): string {
		$stale_cache_in_use = false;
		$cache              = self::get_cache();

		if ( false === $cache ) {
			$cache              = self::get_stale_cache();
			$stale_cache_in_use = true;
		}

		if ( ! empty( $cache ) ) {
			$html = self::prepare_response( $cache );

			self::send_header( 'HIT', $stale_cache_in_use );

			// Cached content is already escaped.
			return $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// Schedule a single event to generate the cache immediately.
		if ( ! wp_next_scheduled( 'alleyvate_404_cache_single' ) ) {
			wp_schedule_single_event( time(), 'alleyvate_404_cache_single' );
		}

		self::send_header( 'MISS' );

		// If no cache, return an empty string.
		return '';
	}

	/**
	 * Send X-Alleyvate HTTP Header.
	 *
	 * @param string $type HIT or MISS.
	 * @param bool   $stale Whether the stale cache is in use. Default false.
	 */
	public static function send_header( string $type, bool $stale = false ): void {

		if ( headers_sent() ) {
			return;
		}

		if ( ! $stale && 'HIT' === $type ) {
			header( 'X-Alleyvate-404-Cache: HIT' );
		} elseif ( $stale && 'HIT' === $type ) {
			header( 'X-Alleyvate-404-Cache: HIT (stale)' );
		} elseif ( 'MISS' === $type ) {
			header( 'X-Alleyvate-404-Cache: MISS' );
		}
	}

	/**
	 * Start output buffering, so we can cache the 404 page.
	 *
	 * @global WP_Query $wp_query WordPress database access object.
	 */
	public static function action__wp(): void {
		if ( isset( $_SERVER['REQUEST_URI'] ) && self::TEMPLATE_GENERATOR_URI === $_SERVER['REQUEST_URI'] ) {
			global $wp_query;

			if ( ! $wp_query->is_404() ) {
				return;
			}

			// Clean up any buffer first.
			ob_end_clean();

			ob_start( [ self::class, 'finish_output_buffering' ] );
		}
	}

	/**
	 * Finish output buffering.
	 *
	 * @global WP_Query $wp_query WordPress database access object.
	 *
	 * @param string $buffer Buffer.
	 * @return string
	 */
	public static function finish_output_buffering( string $buffer ): string {
		global $wp_query;

		if ( ! $wp_query->is_404() ) {
			return $buffer;
		}

		if ( is_user_logged_in() ) {
			return $buffer;
		}

		if ( ! self::get_cache() && ! empty( $buffer ) ) {
			self::set_cache( $buffer );
		}

		return $buffer;
	}

	/**
	 * Get cache.
	 *
	 * @return mixed
	 */
	public static function get_cache(): mixed {
		return wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Get stale cache.
	 *
	 * @return mixed
	 */
	public static function get_stale_cache(): mixed {
		return wp_cache_get( self::STALE_CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Set cache.
	 *
	 * @param string $buffer The Output Buffer.
	 */
	public static function set_cache( string $buffer ): void {
		wp_cache_set( self::CACHE_KEY, $buffer, self::CACHE_GROUP, self::CACHE_TIME ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
		wp_cache_set( self::STALE_CACHE_KEY, $buffer, self::CACHE_GROUP, self::STALE_CACHE_TIME ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
	}

	/**
	 * Delete cache.
	 */
	public static function delete_cache(): void {
		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
		wp_cache_delete( self::STALE_CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Prepare response.
	 *
	 * @param string $content The content.
	 * @return string
	 */
	public static function prepare_response( string $content ): string {
		// To avoid analytics issues, replace the Generator URI with the requested URI.
		$uri = sanitize_text_field( $_SERVER['REQUEST_URI'] ?? '' );

		return str_replace(
			[
				self::TEMPLATE_GENERATOR_URI,
				wp_json_encode( self::TEMPLATE_GENERATOR_URI ),
				esc_html( self::TEMPLATE_GENERATOR_URI ),
				esc_url( self::TEMPLATE_GENERATOR_URI ),
			],
			[
				$uri,
				wp_json_encode( $uri ),
				esc_html( $uri ),
				esc_url( $uri ),
			],
			$content
		);
	}

	/**
	 * Spin up a request to the guaranteed 404 page to populate the cache.
	 */
	public static function trigger_404_page_cache(): void {
		$url = home_url( self::TEMPLATE_GENERATOR_URI, 'https' );

		// Replace http with https to ensure the styles don't get blocked due to insecure content.
		$url = str_replace( 'http://', 'https://', $url );

		// This request will populate the cache using output buffering.
		if ( \function_exists( 'wpcom_vip_file_get_contents' ) ) {
			wpcom_vip_file_get_contents( $url );
		} else {
			wp_remote_get( $url ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		}
	}
}
