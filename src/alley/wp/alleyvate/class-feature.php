<?php
/**
 * Abstract class file for Feature
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
abstract class Feature {
	/**
	 * Value to use for a very early priority when hooking.
	 */
	public const PRIORITY_VERY_EARLY = -9999;

	/**
	 * Value to use for a very late priority when hooking.
	 */
	public const PRIORITY_VERY_LATE = 9999;

	/**
	 * Boot the feature.
	 */
	abstract public function boot(): void;
}
