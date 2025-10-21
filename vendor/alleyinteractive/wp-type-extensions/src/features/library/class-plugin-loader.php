<?php
/**
 * Plugin_Loader class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features\Library;

use Alley\WP\Types\Feature;
use Alley\WP\WP_Plugin_Loader;

/**
 * Makes the plugin loader available in a feature.
 */
final class Plugin_Loader implements Feature {
	/**
	 * Constructor.
	 *
	 * @param string[] $plugins List of plugins to load.
	 */
	public function __construct(
		private readonly array $plugins,
	) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		// This class loads the plugin immediately.
		new WP_Plugin_Loader( $this->plugins );
	}
}
