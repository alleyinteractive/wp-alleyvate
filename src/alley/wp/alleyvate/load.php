<?php
/**
 * `load()` function
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate;

use Alley\WP\Features\Group;

/**
 * Load plugin features.
 */
function load(): void {
	// Bail if the Alleyvate feature class isn't loaded to prevent a fatal error.
	if ( ! class_exists( Feature::class ) ) {
		return;
	}

	$plugin = new Group(
		new Site_Health_Panel(),
		new Feature(
			'cache_slow_queries',
			new Features\Cache_Slow_Queries(),
		),
		new Feature(
			'clean_admin_bar',
			new Features\Clean_Admin_Bar(),
		),
		new Feature(
			'disable_attachment_routing',
			new Features\Disable_Attachment_Routing(),
		),
		new Feature(
			'disable_comments',
			new Features\Disable_Comments(),
		),
		new Feature(
			'disable_custom_fields_meta_box',
			new Features\Disable_Custom_Fields_Meta_Box(),
		),
		new Feature(
			'disable_dashboard_widgets',
			new Features\Disable_Dashboard_Widgets(),
		),
		new Feature(
			'disable_password_change_notification',
			new Features\Disable_Password_Change_Notification(),
		),
		new Feature(
			'disable_sticky_posts',
			new Features\Disable_Sticky_Posts(),
		),
		new Feature(
			'disable_trackbacks',
			new Features\Disable_Trackbacks(),
		),
		new Feature(
			'disallow_file_edit',
			new Features\Disallow_File_Edit(),
		),
		new Feature(
			'enable_jetpack_safe_mode',
			new Features\Enable_Jetpack_Safe_Mode(),
		),
		new Feature(
			'login_nonce',
			new Features\Login_Nonce(),
		),
		new Feature(
			'prevent_framing',
			new Features\Prevent_Framing(),
		),
		new Feature(
			'redirect_guess_shortcircuit',
			new Features\Redirect_Guess_Shortcircuit(),
		),
		new Feature(
			'user_enumeration_restrictions',
			new Features\User_Enumeration_Restrictions(),
		),
		new Feature(
			'remove_shortlink',
			new Features\Remove_Shortlink(),
		),
		new Feature(
			'disable_pantheon_constant_overrides',
			new Features\Disable_Pantheon_Constant_Overrides(),
		),
		new Feature(
			'force_two_factor_authentication',
			new Features\Force_Two_Factor_Authentication(),
		),
		new Feature(
			'disable_deep_pagination',
			new Features\Disable_Deep_Pagination(),
		),
		new Feature(
			'disable_block_editor_rest_api_preload_paths',
			new Features\Disable_Block_Editor_Rest_Api_Preload_Paths(),
		),
	);

	$plugin->boot();
}
