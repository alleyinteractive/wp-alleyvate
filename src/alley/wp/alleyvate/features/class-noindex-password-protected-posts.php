<?php
/**
 * Class file for Noindex_Password_Protected_Posts
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
 * Adds noindex to the robots meta tag content for password-protected posts.
 */
final class Noindex_Password_Protected_Posts implements Feature {

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'wp_robots', [ $this, 'filter_robots_content' ] );
	}

	/**
	 * Filters the robots meta tag content to add a noindex directive.
	 *
	 * @param array $robots Associative array of directives.
	 *
	 * @return array
	 */
	public function filter_robots_content( array $robots ): array {
		if ( post_password_required() ) {
			$robots['noindex'] = true;
		}
		return $robots;
	}
}
