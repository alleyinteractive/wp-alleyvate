<?php
/**
 * Class file for Feature
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate;

/**
 * An Alleyvate feature.
 */
final class Feature implements \Alley\WP\Types\Feature {
	private bool $booted = false;

	/**
	 * Constructor.
	 *
	 * @param string  $handle Feature handle.
	 * @param Feature $origin Feature.
	 */
	public function __construct(
		private string $handle,
		private \Alley\WP\Types\Feature $origin,
	) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		// Alleyvate features load after all plugins and themes have had a chance to add filters.
		add_action( 'after_setup_theme', [ $this, 'filtered_boot' ] );
		add_filter( 'debug_information', [ $this, 'add_debug_information' ] );
	}

	/**
	 * Maybe boot the origin feature.
	 */
	public function filtered_boot(): void {
		$load = true;

		/**
		 * Filters whether to load an Alleyvate feature.
		 *
		 * @param bool   $load   Whether to load the feature. Default true.
		 * @param string $handle Feature handle.
		 */
		$load = apply_filters( 'alleyvate_load_feature', $load, $this->handle );

		/**
		 * Filters whether to load the given Alleyvate feature.
		 *
		 * The dynamic portion of the hook name, `$this->$this->handle`, refers to the
		 * machine name for the feature.
		 *
		 * @param bool $load Whether to load the feature. Default true.
		 */
		$load = apply_filters( "alleyvate_load_{$this->handle}", $load );

		if ( $load ) {
			$this->booted = true;
			$this->origin->boot();
		}
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

		if ( ! isset( $info['wp-alleyvate'] ) ) {
			$info['wp-alleyvate'] = [
				'label'       => __( 'Alleyvate', 'alley' ),
				'description' => __( 'Diagnostic information about the Alleyvate plugin and which features are enabled.', 'alley' ),
				'fields'      => [],
			];
		}

		$info['wp-alleyvate']['fields'][] = [
			'label' => sprintf(
				/* translators: %s: Feature name. */
				__( 'Feature: %s', 'alley' ),
				$this->handle,
			),
			'value' => $this->booted ? __( 'Enabled', 'alley' ) : __( 'Disabled', 'alley' ),
		];

		return $info;
	}
}
