<?php
/**
 * Class file for Disable_Login_Cache
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
 * Adds an HTTP header to the login page to disable storing of that page in
 * cache. The login page already requires cache revalidation, but it doesn't
 * block cache's from storing a local copy. Enabling this allows us to
 * remove the risk of local changes from being ignored because a stored cache
 * copy.
 */
final class Disable_Login_Cache implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'nocache_headers', [ $this, 'add_no_store_to_login' ] );
	}

	/**
	 * Adds the `no-store` flag to the `Cache-Control` headers.
	 *
	 * @param array $headers The headers array.
	 * @return array
	 */
	public function add_no_store_to_login( $headers ): array {
		if ( ! is_array( $headers ) ) {
			$headers = [];
		}

		if ( 'wp-login.php' !== ( $GLOBALS['pagenow'] ?? '' ) ) {
			return $headers;
		}

		$headers['Cache-Control'] = 'no-cache, must-revalidate, max-age=0, no-store';

		return $headers;
	}

}
