<?php
/**
 * Class file for Site_Health
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

use function Alley\WP\Alleyvate\available_features;
use function Alley\WP\Alleyvate\should_load_feature;

/**
 * Site Health feature.
 */
final class Site_Health implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'debug_information', [ $this, 'add_debug_information' ] );
	}

	/**
	 * Add debug information to the Site Health screen.
	 *
	 * @param array $info Debug information.
	 * @return array
	 */
	public function add_debug_information( $info ): array {
		if ( ! \is_array( $info ) ) {
			$info = [];
		}

		$info['wp-alleyvate'] = [
			'label'       => __( 'Alleyvate', 'alley' ),
			'description' => __( 'Diagnostic information about the Alleyvate plugin and which features are enabled.', 'alley' ),
			'fields'      => array_map(
				fn ( string $handle ) => [
					'label' => sprintf(
						/* translators: %s: Feature name. */
						__( 'Feature: %s', 'alley' ),
						$handle,
					),
					'value' => should_load_feature( $handle ) ? __( 'Enabled', 'alley' ) : __( 'Disabled', 'alley' ),
				],
				array_keys( available_features() ),
			),
		];

		return $info;
	}
}
