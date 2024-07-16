<?php
/**
 * Class file for Disable_Password_Change_Notification
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
 * Fully disables password change notifications.
 */
final class Disable_Password_Change_Notification implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		remove_action( 'after_password_reset', 'wp_password_change_notification' );
	}
}
