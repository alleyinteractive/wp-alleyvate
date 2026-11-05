<?php
/**
 * Widget_Feature class file
 *
 * Register a single widget using the feature syntax.
 *
 * @package wp-type-extensions
 */

declare(strict_types=1);

namespace Alley\WP\Features;

use Alley\WP\Types\Feature;
use WP_Widget;

/**
 * Boot a single widget.
 */
final class Widget_Feature implements Feature {
	/**
	 * Set up.
	 *
	 * @phpstan-param class-string<WP_Widget>|WP_Widget $widget
	 *
	 * @param string|WP_Widget $widget Widget feature instance.
	 */
	public function __construct(
		private readonly WP_Widget|string $widget,
	) {}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action(
			'widgets_init',
			function (): void {
				$widget = $this->widget;

				if ( $this->widget instanceof WP_Widget ) {
					$widget = $this->widget::class;
				}

				if ( empty( $widget ) ) {
					return;
				}

				register_widget( $widget );
			}
		);
	}
}
