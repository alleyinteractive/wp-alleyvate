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

use Stringable;

/**
 * Describes an Alleyvate feature.
 */
interface Feature {
	/**
	 * Feature handle.
	 *
	 * @return string|Stringable
	 */
	public function handle(): string|Stringable;

	/**
	 * Boot the feature.
	 */
	public function boot(): void;
}
