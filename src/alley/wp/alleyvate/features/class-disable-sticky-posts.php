<?php
/**
 * Class file for Disable_Sticky_Posts
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
 * Fully disables sticky posts.
 */
final class Disable_Sticky_Posts implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'pre_option_sticky_posts', '__return_empty_array', 9999 );
		add_filter( 'is_sticky', '__return_false', 9999 );
		add_action( 'admin_head', [ $this, 'on_admin_head' ] );
		add_filter( 'rest_prepare_post', [ $this, 'on_rest_prepare_post' ], 9999 );
	}

	/**
	 * Remove sticky posts from the admin edit screen.
	 *
	 * Includes a script to remove the checkbox from the quick edit screen that
	 * will cover the browsers that :has is not supported in yet.
	 */
	public function on_admin_head(): void {
		?>
		<style>
			.wp-admin #sticky-span,
			label:has(input[name="sticky"]) {
				display: none !important;
			}
		</style>

		<script>
			document.addEventListener('DOMContentLoaded', function() {
				var checkbox = document.querySelector('input[name="sticky"]');

				if (checkbox) {
					checkbox.parentNode.parentNode.removeChild(checkbox.parentNode);
				}
			});
		</script>
		<?php
	}

	/**
	 * Filters a REST response to make it look like the user can't stick posts.
	 *
	 * @see \gutenberg_add_target_schema_to_links().
	 *
	 * @param \WP_REST_Response $response The response object.
	 * @return \WP_REST_Response Updated respose.
	 */
	public function on_rest_prepare_post( $response ) {
		$response->remove_link( 'https://api.w.org/action-sticky' );

		return $response;
	}
}
