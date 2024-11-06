<?php
/**
 * Term_Events trait file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Events;

use Closure;

/**
 * Term Event Subscribers
 */
trait Term_Events {
	/**
	 * Boot the trait.
	 */
	public static function boot_term_events(): void {
		static::subscribe_to_core_events();
	}

	/**
	 * Subscribe to the core WordPress events for the model.
	 */
	protected static function subscribe_to_core_events() {
		$object_name = static::get_object_name();

		add_action( 'created_term', static::get_term_event_callback( 'created', $object_name ), 10, 3 );
		add_action( 'edit_terms', static::get_term_event_callback( 'updating', $object_name ), 10, 3 );
		add_action( 'edited_term', static::get_term_event_callback( 'updated', $object_name ), 10, 3 );
		add_action( 'pre_delete_term', static::get_term_event_callback( 'deleting', $object_name ), 10, 2 );
		add_action( 'delete_term', static::get_term_event_callback( 'deleted', $object_name ), 10, 4 );
	}

	/**
	 * Generate a callback for a WordPress action.
	 *
	 * @param string $event Event name to fire.
	 * @param string $taxonomy Taxonomy to limit to.
	 */
	protected static function get_term_event_callback( string $event, string $taxonomy ): Closure {
		return function ( $term_id, $tt_id, $term_taxonomy = null, $term = null ) use ( $event, $taxonomy ): void {
			// Account for actions that have taxonomy as the second argument.
			if ( ( empty( $term_taxonomy ) || ! is_string( $term_taxonomy ) ) && is_string( $tt_id ) ) {
				$term_taxonomy = $tt_id;
			}

			if ( $taxonomy !== $term_taxonomy ) {
				return;
			}

			// Use the term object passed from the action for 'deleting'.
			if ( 'deleted' === $event && $term ) {
				$model = static::new_from_existing( (array) $term );
			} else {
				$model = static::find( $term_id );
			}

			if ( $model ) {
				$model->fire_model_event( $event );
			}
		};
	}
}
