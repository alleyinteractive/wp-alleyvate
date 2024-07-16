<?php
/**
 * Site_Health_Panel class file
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate;

use Alley\WP\Types\Feature;

/**
 * Site Health panel for Alleyvate features.
 */
final class Site_Health_Panel implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'debug_information', [ $this, 'add_debug_panel' ], 0 );
	}

	/**
	 * Add debug information for the feature.
	 *
	 * @param array<string, array{label: string, description: string, fields: array<int, mixed>}> $info Debug information.
	 * @return array<string, array{label: string, description: string, fields: array<int, mixed>}> Debug information.
	 */
	public function add_debug_panel( $info ): array {
		if ( ! \is_array( $info ) ) {
			$info = [];
		}

		$info['wp-alleyvate'] = [
			'label'       => __( 'Alleyvate', 'alley' ),
			'description' => __( 'Diagnostic information about the Alleyvate plugin and which features are enabled.', 'alley' ),
			'fields'      => [],
		];

		return $info;
	}
}
