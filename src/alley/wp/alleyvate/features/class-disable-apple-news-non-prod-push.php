<?php
/**
 * Class file for Disable_Apple_News_Non_Prod_Push
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
 * Disables Apple News Push on Non Production Environments.
 */
final class Disable_Apple_News_Non_Prod_Push implements Feature {
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
	public function filter_apple_news_skip_push( bool $skip ): bool {
		// If we are on a production environment, don't modify the value.
		if ( 'production' === wp_get_environment_type() ) {
			return $skip;
		}

		// All other cases, return true.
		return true;
	}
}
