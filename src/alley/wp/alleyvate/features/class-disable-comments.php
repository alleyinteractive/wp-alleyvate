<?php
/**
 * Class file for Disable_Comments
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
 * Fully disables comments.
 */
final class Disable_Comments implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		// TODO: Is this necessary after coersion of field values?
		// Turn off comments for all posts generically when attempting to save a comment.
		add_filter( 'comments_open', '__return_false', PHP_INT_MAX );

		// Coerce the comment_status field to 'closed' in all cases.
		add_filter( 'edit_post_comment_status', [ self::class, 'filter__comment_status' ] );
		add_filter( 'pre_post_comment_status', [ self::class, 'filter__comment_status' ] );
		add_filter( 'post_comment_status', [ self::class, 'filter__comment_status' ] );
	}

	/**
	 * Generic filter callback for comment_status setting on post objects. Always returns 'closed'.
	 *
	 * @return string The static value 'closed'.
	 */
	public static function filter__comment_status(): string {
		return 'closed';
	}
}
