<?php
/**
 * Interface file for Feature. This interface not subject to semantic-versioning constraints
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Internals;

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
