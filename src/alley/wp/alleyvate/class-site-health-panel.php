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
	public function boot(): void {
		add_filter( 'debug_information', [ $this, 'add_debug_panel' ], 0 );
		add_filter( 'debug_information', [ $this, 'sort_debug_panel_features' ], PHP_INT_MAX );
	}

	/**
	 * @param array<string, array{label: string, description: string, fields: array<int, array<string, mixed>>}> $info
	 * @return array<string, array{label: string, description: string, fields: array<int, array<string, mixed>>}>
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
	 * @param array<string, array{label: string, description: string, fields: array<int, array<string, mixed>>}> $info
	 * @return array<string, array{label: string, description: string, fields: array<int, array<string, mixed>>}>
	 */
	public function sort_debug_panel_features( array $info ): array {
		$panel = 'wp-alleyvate';

		/** @var array<int, array<string, mixed>> $fields */
		$fields = $info[ $panel ]['fields'] ?? [];

		uasort(
			$fields,
			/**
			 * @param array<string, mixed> $a
			 * @param array<string, mixed> $b
			 */
			static function ( array $a, array $b ): int {
				$labelA = isset( $a['label'] ) && is_string( $a['label'] ) ? $a['label'] : '';
				$labelB = isset( $b['label'] ) && is_string( $b['label'] ) ? $b['label'] : '';
				return strnatcasecmp( $labelA, $labelB );
			}
		);

		$info[ $panel ]['fields'] = $fields;

		return $info;
	}
}
