<?php
/**
 * Class file for Disable_Block_Editor_Rest_Api_Preload_Paths
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

declare( strict_types=1 );

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Types\Feature;

/**
 * Disables the preloading of the blocks which happens on all edit post pages.
 */
final class Disable_Block_Editor_Rest_Api_Preload_Paths implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter(
			'block_editor_rest_api_preload_paths',
			[ self::class, 'filter__block_editor_rest_api_preload_paths' ],
			9999
		);
	}

	/**
	 * Filter the block editor REST API preload paths.
	 *
	 * @param mixed[] $paths The paths to preload.
	 *
	 * @return mixed[] The filtered paths.
	 */
	public static function filter__block_editor_rest_api_preload_paths( $paths ) {
		if ( ! \is_array( $paths ) ) {
			return [];
		}
		return array_values(
			array_filter(
				$paths,
				function ( $v ) {
					// Remove the blocks preload path for performance reasons.
					return ! \is_string( $v ) || ! str_starts_with( $v, '/wp/v2/blocks?context=edit' );
				},
			)
		);
	}
}
