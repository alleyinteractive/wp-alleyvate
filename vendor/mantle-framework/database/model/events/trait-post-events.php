<?php
/**
 * Post_Events trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Events;

use Closure;
use function Alley\WP\add_filter_side_effect;

/**
 * Post Event Subscribers
 */
trait Post_Events {
	/**
	 * Boot the trait.
	 */
	public static function boot_post_events(): void {
		static::subscribe_to_core_events();
	}

	/**
	 * Subscribe to the core WordPress events for the model.
	 */
	protected static function subscribe_to_core_events() {
		$post_type = static::get_object_name();

		add_filter_side_effect(
			'wp_insert_post_data',
			function ( $data, $postarr ) use ( $post_type ): void {
				// Skip if the ID isn't found or the post type is incorrect.
				if ( empty( $postarr['ID'] ) || empty( $data['post_type'] ) || $post_type !== $data['post_type'] ) {
					return;
				}

				$updating = ! empty( $postarr['ID'] );
				$model    = static::find( $postarr['ID'] );

				if ( ! $model ) {
					return;
				}

				$model->fire_model_event( $updating ? 'updating' : 'creating' );
			},
			10,
			2
		);

		\add_action(
			'save_post',
			function ( $post_id, \WP_Post $post, $update ) use ( $post_type ): void {
				if ( $post_type !== $post->post_type ) {
					return;
				}

				$model = static::find( $post_id );
				if ( ! $model ) {
					return;
				}

				if ( 'trash' === $post->post_status ) {
					$model->fire_model_event( 'trashed' );
				} else {
					$model->fire_model_event( $update ? 'updated' : 'created' );
				}
			},
			10,
			3
		);

		\add_action( 'wp_trash_post', static::get_post_event_callback( 'trashing', $post_type ) );
		\add_action( 'before_delete_post', static::get_post_event_callback( 'deleting', $post_type ) );
		\add_action( 'deleted_post', static::get_post_event_callback( 'deleted', $post_type ) );
	}

	/**
	 * Generate a callback for a WordPress action.
	 *
	 * @param string $event Event name to fire.
	 * @param string $post_type Post type to limit to.
	 */
	protected static function get_post_event_callback( string $event, string $post_type ): Closure {
		return function ( $post_id ) use ( $event, $post_type ): void {
			$post = \get_post( $post_id );
			if ( empty( $post ) || $post_type !== $post->post_type ) {
				return;
			}

			$model = static::find( $post_id );
			if ( $model ) {
				$model->fire_model_event( $event );
			}
		};
	}
}
