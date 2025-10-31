<?php
/**
 * WP_CLI_Feature class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features;

use Alley\WP\Types\Feature;
use WP_CLI;

/**
 * Boot a feature only WP-CLI loads.
 */
final class WP_CLI_Feature implements Feature {
	/**
	 * Set up.
	 *
	 * @param Feature $origin Feature instance.
	 */
	public function __construct(
		private readonly Feature $origin,
	) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'cli_init', [ $this->origin, 'boot' ] );
		} elseif ( class_exists( 'WP_CLI' ) ) {
			/*
			 * This is being invoked in a WP-CLI package or in a similar context where
			 * WordPress hasn't yet been loaded.
			 *
			 * @see https://github.com/buddypress/wp-cli-buddypress/issues/18
			 */
			WP_CLI::add_hook( 'before_wp_load', [ $this->origin, 'boot' ] );
		}
	}
}
