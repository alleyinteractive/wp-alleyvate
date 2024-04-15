<?php
/**
 * Class file for Force_Two_Factor_Authentication
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
 * Forces 2FA for users with Edit permissions or higher when 2FA is available.
 */
final class Force_Two_Factor_Authentication implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {

	}
}
