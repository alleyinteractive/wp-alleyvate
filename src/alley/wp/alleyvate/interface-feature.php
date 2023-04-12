<?php
/**
 * Interface file for Feature
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate;

/**
 * Describes an Alleyvate feature.
 */
interface Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void;
}
