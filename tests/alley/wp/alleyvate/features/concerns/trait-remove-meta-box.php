<?php
/**
 * Class file for Test_Disable_Custom_Fields_Meta_Box
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features\Concerns;

use Alley\WP\Alleyvate\Feature;

/**
 * Test the removal of a meta box.
 */
trait Remove_Meta_Box {
	use \Mantle\Testing\Concerns\Admin_Screen;

	/**
	 * Test the removal of a meta box.
	 *
	 * @param Feature $feature  Feature instance.
	 * @param string  $id       Meta box ID.
	 * @param string  $screen   Screen to test on.
	 * @param string  $context  Meta box context.
	 * @param string  $priority Meta box priority.
	 * @return void
	 */
	protected function assertMetaBoxRemoval( Feature $feature, string $id, string $screen = 'post', string $context = 'normal', string $priority = 'default' ): void {
		$post = self::factory()->post->create_and_get();

		// Load files required to get $wp_meta_boxes global.
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/theme.php';
		require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';

		// Setup metaboxes global and confirm 'custom fields' are in it.
		set_current_screen( $screen );
		register_and_do_post_meta_boxes( $post );

		global $wp_meta_boxes;
		$this->assertNotEmpty(
			$wp_meta_boxes[ $screen ][ $context ][ $priority ][ $id ] ?? null,
			"Meta box {$id} was not registered."
		);

		// Activate feature.
		$feature->boot();

		register_and_do_post_meta_boxes( $post );

		// Confirm that the metabox is removed.
		$this->assertFalse(
			$wp_meta_boxes[ $screen ][ $context ][ $priority ][ $id ],
			"Meta box {$id} was not removed."
		);
	}
}
