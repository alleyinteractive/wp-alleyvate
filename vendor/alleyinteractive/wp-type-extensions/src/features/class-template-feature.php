<?php
/**
 * Template_Feature class file
 *
 * @package wp-type-extensions
 */

namespace Alley\WP\Features;

use Alley\WP\Types\Feature;

/**
 * Boot a feature only when templates load.
 */
final class Template_Feature implements Feature {
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
		add_action( 'template_redirect', [ $this->origin, 'boot' ] );
	}
}
