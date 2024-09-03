<?php
/**
 * Class file for Disable_Site_Health_Directories
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Types\Feature;

/**
 * Disable_Site_Health_Directories feature.
 */
final class Disable_Site_Health_Directories implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_filter( 'rest_pre_dispatch', [ $this, 'filter_rest_pre_dispatch' ], 10, 3 );
		add_filter( 'debug_information', [ $this, 'filter_debug_information' ] );
	}

	/**
	 * Filter REST API requests to remove Site Health directories.
	 *
	 * @param mixed            $result Response to replace the requested version with. Can be anything a normal endpoint can return, or null to not hijack the request.
	 * @param \WP_REST_Server  $server Server instance.
	 * @param \WP_REST_Request $request Request used to generate the response.
	 * @return mixed Response to replace the requested version with.
	 */
	public function filter_rest_pre_dispatch( $result, $server, $request ) {
		if ( $request->get_route() === '/wp-site-health/v1/directory-sizes' ) {
			return new \WP_Error( 'rest_disabled', 'REST API endpoint disabled.', [ 'status' => 403 ] );
		}

		return $result;
	}

	/**
	 * Filter debug information to remove Site Health directories.
	 *
	 * @param array<string, array{label: string, description: string, fields: array<int, mixed>}> $info Debug information.
	 * @return array<string, array{label: string, description: string, fields: array<int, mixed>}> Debug information.
	 */
	public function filter_debug_information( $info ): array {
		if ( ! \is_array( $info ) ) {
			$info = [];
		}

		if ( isset( $info['wp-paths-sizes'] ) ) {
			unset( $info['wp-paths-sizes'] );
		}

		return $info;
	}
}
