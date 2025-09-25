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
		add_filter( 'debug_information', [ $this, 'sort_debug_panel_features' ], PHP_INT_MAX );
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

	/**
	 * Sorts the Alleyvate features in the Site Health panel alphabetically.
	 *
	 * @param array<string, array{label: string, description: string, fields: array<int, mixed>}> $info Debug information.
	 * @return array<string, array{label: string, description: string, fields: array<int, mixed>}> Debug information.
	 */
	public function sort_debug_panel_features( $info ): array {
		$panel = 'wp-alleyvate';

		if ( ! isset( $info[ $panel ]['fields'] ) || ! is_array( $info[ $panel ]['fields'] ) ) {
			return $info;
		}

		$fields = $info[ $panel ]['fields'];

		uasort( $fields, function ( $a, $b ) {
			$label_a = $a['label'] ?? '';
			$label_b = $b['label'] ?? '';

			return strnatcasecmp( $label_a, $label_b );
		} );

		$info[ $panel ]['fields'] = $fields;

		return $info;
	}
}
