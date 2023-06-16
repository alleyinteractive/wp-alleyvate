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
class Disable_Apple_News_No_Prod_Push implements Feature {
	/**
	 * Store if this is a production environment.
	 *
	 * @var [type]
	 */
	private $is_production;

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'apple_news_skip_push', [ $this, 'filter_apple_news_skip_push' ], 1, 100 );
		$this->is_production = $this->is_production_environment();
	}

	/**
	 * Filter the Apple News push skip flag. If we are not on a production environment, skip the push.
	 *
	 * @param bool $skip Should we skip the Apple News push.
	 */
	public function filter_apple_news_skip_push( bool $skip ) {
		// If we are on a production environment, don't modify the value.
		if ( $this->is_production ) {
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

	/**
	 * Detect if we are on a production environment.
	 *
	 * Note: I thought about using https://developer.wordpress.org/reference/functions/wp_get_environment_type/
	 * but it defaults to 'production' if no value is set, which did not seem ideal for this use case.
	 *
	 * @return boolean
	 */
	protected function is_production_environment(): bool {
		// If we are not on a production environment according to WP_ENV, return true.
		if ( defined( 'WP_ENV' ) && 'production' === WP_ENV ) {
			return true;
		}
		// If we are on Pantheon LIVE, return true.
		if ( isset( $_ENV['PANTHEON_ENVIRONMENT'] ) && 'live' === $_ENV['PANTHEON_ENVIRONMENT'] ) {
			return true;
		}
		// If we are on VIP Production, don't modify the value.
		if ( defined( 'VIP_GO_ENV' ) && VIP_GO_ENV === 'production' ) {
			return true;
		}
		return false;
	}
}
