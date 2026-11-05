<?php
/**
 * Widget_Feature class file
 *
 * Register several widgets using the feature syntax.
 *
 * @package wp-type-extensions
 */

declare(strict_types=1);

namespace Alley\WP\Features;

use Alley\WP\Types\Feature;
use WP_Widget;

/**
 * Boot several widgets.
 */
final class Widget_Features implements Feature {
	/**
	 * Widgets to include.
	 *
	 * @phpstan-var array<class-string<WP_Widget>|WP_Widget>
	 *
	 * @var class-string<WP_Widget>[]|WP_Widget[]
	 */
	private array $widgets = [];

	/**
	 * Constructor.
	 *
	 * @phpstan-param class-string<WP_Widget>|WP_Widget|array<class-string<WP_Widget>|WP_Widget> ...$widgets
	 *
	 * @param string|string[]|WP_Widget|WP_Widget[] ...$widgets Widgets.
	 */
	public function __construct( ...$widgets ) {
		foreach ( $widgets as $widget ) {
			if ( is_array( $widget ) ) {
				array_push( $this->widgets, ...$widget );
			} else {
				$this->widgets[] = $widget;
			}
		}
	}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action(
			'widgets_init',
			function (): void {
				foreach ( $this->widgets as $widget_class ) {
					if ( $widget_class instanceof WP_Widget ) {
						register_widget( $widget_class::class );
					} elseif ( is_string( $widget_class ) && ! empty( $widget_class ) ) {
						register_widget( $widget_class );
					} else {
						_doing_it_wrong(
							__METHOD__,
							esc_html__( 'To register widget features, you need to pass a WP_Widget or string.', 'wp-type-extensions' ),
							'1.0.0'
						);
					}
				}
			}
		);
	}

	/**
	 * Include widgets.
	 *
	 * @phpstan-param class-string<WP_Widget>|WP_Widget|array<class-string<WP_Widget>|WP_Widget> ...$widgets
	 *
	 * @param string|string[]|WP_Widget|WP_Widget[] ...$widgets Widgets to include.
	 */
	public function include( ...$widgets ): void {
		foreach ( $widgets as $widget ) {
			if ( is_array( $widget ) ) {
				array_push( $this->widgets, ...$widget );
			} else {
				$this->widgets[] = $widget;
			}
		}
	}
}
