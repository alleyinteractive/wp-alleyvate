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
	 * @param array<string, array{label: string, description: string, fields: array<int, array<string, mixed>>}> $info Debug information.
	 * @return array<string, array{label: string, description: string, fields: array<int, array<string, mixed>>}> Debug information.
	 */
	public function add_debug_panel( array $info ): array {
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
	 * @param array<string, array{label: string, description: string, fields: array<int, array<string, mixed>>}> $info Debug information.
	 * @return array<string, array{label: string, description: string, fields: array<int, array<string, mixed>>}> Debug information.
	 */
	public function sort_debug_panel_features( array $info ): array {
		$panel = 'wp-alleyvate';

		$fields = $info[ $panel ]['fields'] ?? [];

		uasort(
			$fields,
			/**
			 * Sorts the fields alphabetically by their labels.
			 *
			 * @param array<string, mixed> $a
			 * @param array<string, mixed> $b
			 */
			static function ( array $a, array $b ): int {
				$label_a = isset( $a['label'] ) && is_string( $a['label'] ) ? $a['label'] : '';
				$label_b = isset( $b['label'] ) && is_string( $b['label'] ) ? $b['label'] : '';
				return strnatcasecmp( $label_a, $label_b );
			}
		);

		$info[ $panel ]['fields'] = $fields;

		return $info;
	}
}
