<?php
/**
 * Class file for Disable_Attachment_Routes
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
 * Disables routes to attachment pages.
 */
final class Disable_Attachment_Routes implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'rewrite_rules_array', [ self::class, 'filter__rewrite_rules_array' ], 9999 );
	}

	/**
	 * Removes rewrite rules related to attachments that are associated with a post.
	 *
	 * @param array $rules Rewrite rules to be filtered.
	 *
	 * @return array Filtered rewrite rules.
	 */
	public static function filter__rewrite_rules_array( array $rules ): array {
		foreach ( $rules as $regex => $rewrite ) {
			if ( str_contains( $rewrite, 'attachment=$' ) ) {
				unset( $rules[ $regex ] );
			}
		}

		return $rules;
	}
}
