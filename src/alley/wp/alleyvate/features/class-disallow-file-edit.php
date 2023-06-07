<?php
/**
 * Class file for Disallow_File_Edit
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
 * Disallow theme/plugin editing in the filesystem to safeguard against unexpected code changes.
 */
final class Disallow_File_Edit extends Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		if ( ! \defined( 'DISALLOW_FILE_EDIT' ) ) {
			\define( 'DISALLOW_FILE_EDIT', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
		}
	}
}
