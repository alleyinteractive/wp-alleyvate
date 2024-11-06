<?php
/**
 * Has_Events class file.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPressVIPMinimum.Variables.VariableAnalysis.StaticOutsideClass
 */

namespace Mantle\Database\Model\Concerns;

use Mantle\Events\Dispatcher;
use InvalidArgumentException;

/**
 * Model Event Listener
 */
trait Has_Events {
	/**
	 * The event dispatcher instance.
	 *
	 * @var \Mantle\Events\Dispatcher|null
	 */
	protected static $dispatcher;

	/**
	 * Register a model event with the dispatcher.
	 *
	 * @param  string          $event Event name to listen to.
	 * @param  \Closure|string $callback
	 * @throws InvalidArgumentException Thrown on a missing dispatcher.
	 */
	protected static function register_model_event( $event, $callback ) {
		$name = static::class;
		if ( isset( static::$dispatcher ) ) {
			static::$dispatcher->listen( "model.{$event}: {$name}", $callback );
		} else {
			throw new InvalidArgumentException( "Model event dispatcher not set: [{$name}]" );
		}
	}

	/**
	 * Fire the given event for the model.
	 *
	 * @param string $event Event being fired.
	 * @return mixed
	 */
	public function fire_model_event( $event ) {
		if ( ! isset( static::$dispatcher ) ) {
			return true;
		}

		return static::$dispatcher->dispatch(
			"model.{$event}: " . static::class,
			$this
		);
	}

	/**
	 * Register a creating model event with the dispatcher.
	 *
	 * Note: *is not* supported at this time!
	 *
	 * @param  \Closure|string $callback
	 * @throws \RuntimeException Thrown on use.
	 */
	public static function creating( $callback ): never {
		throw new \RuntimeException( 'Listening to the "creating" event on a model is not supported at this time.' );
	}

	/**
	 * Register a created model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 */
	public static function created( $callback ): void {
		static::register_model_event( 'created', $callback );
	}

	/**
	 * Register a updating model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 */
	public static function updating( $callback ): void {
		static::register_model_event( 'updating', $callback );
	}

	/**
	 * Register a updated model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 */
	public static function updated( $callback ): void {
		static::register_model_event( 'updated', $callback );
	}

	/**
	 * Register a trashing model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 */
	public static function trashing( $callback ): void {
		static::register_model_event( 'trashing', $callback );
	}

	/**
	 * Register a trashed model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 */
	public static function trashed( $callback ): void {
		static::register_model_event( 'trashed', $callback );
	}

	/**
	 * Register a deleting model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 */
	public static function deleting( $callback ): void {
		static::register_model_event( 'deleting', $callback );
	}

	/**
	 * Register a deleted model event with the dispatcher.
	 *
	 * @param  \Closure|string $callback
	 */
	public static function deleted( $callback ): void {
		static::register_model_event( 'deleted', $callback );
	}

	/**
	 * Get the event dispatcher instance.
	 */
	public static function get_event_dispatcher(): Dispatcher {
		return static::$dispatcher;
	}

	/**
	 * Set the event dispatcher instance.
	 *
	 * @param Dispatcher $dispatcher Dispatcher instance.
	 */
	public static function set_event_dispatcher( Dispatcher $dispatcher ): void {
		static::$dispatcher = $dispatcher;
	}

	/**
	 * Unset the event dispatcher for models.
	 */
	public static function unset_event_dispatcher(): void {
		static::$dispatcher = null;
	}

	/**
	 * Get the observable event names.
	 *
	 * @return array
	 */
	public function get_observable_events() {
		return [
			'creating',
			'created',
			'updating',
			'updated',
			'trashing',
			'trashed',
			'deleting',
			'deleted',
		];
	}

	/**
	 * Remove all of the event listeners for the model.
	 */
	public static function flush_event_listeners(): void {
		if ( ! isset( static::$dispatcher ) ) {
			return;
		}

		$instance = new static();

		foreach ( $instance->get_observable_events() as $event ) {
			static::$dispatcher->forget( "model.{$event}: " . static::class );
		}
	}
}
