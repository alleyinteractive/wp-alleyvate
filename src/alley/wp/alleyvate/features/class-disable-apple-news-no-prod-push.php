<?php
/**
 * Class file for Disable_Apple_News_No_Prod_Push
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
 * Disables Apple News Push on Non Production Environments.
 */
final class Disable_Apple_News_No_Prod_Push implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'apple_news_skip_push', [ $this, 'filter_apple_news_skip_push' ], 1, 100 );
	}

	/**
	 * Filter the Apple News push skip flag. If we are not on a production environment, skip the push.
	 *
	 * @param bool $skip Should we skip the Apple News push.
	 */
	public function filter_apple_news_skip_push( bool $skip ) {
		// If we are not on a production environment according to WP_ENV, don't modify the value.
		if ( defined( 'WP_ENV' ) && 'production' === WP_ENV ) {
			return $skip;
		}
		// If we are on Pantheon LIVE, don't modify the value.
		if ( isset( $_ENV['PANTHEON_ENVIRONMENT'] ) && 'live' === $_ENV['PANTHEON_ENVIRONMENT'] ) {
			return $skip;
		}
		// If we are on VIP Production, don't modify the value.
		if ( defined( 'VIP_GO_ENV' ) && VIP_GO_ENV === 'production' ) {
			return $skip;
		}
		// All other cases, return true - but allow it to be filtered.

		/**
		 * Filters the final value in filter_apple_news_skip_push.
		 *
		 * Allow developers to disable this feature.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $skip Should we skip the Apple News push?
		 */
		return apply_filters( 'alleyvate_disable_apple_news_no_prod_push', true );
	}
}
