<?php
/**
 * Dispatcher interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Events;

/**
 * Event Dispatcher Contract
 */
interface Dispatcher {
	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  string|array      $events
	 * @param  \Closure|callable $listener
	 * @return void
	 */
	public function listen( $events, $listener );

	/**
	 * Determine if a given event has listeners.
	 *
	 * @param  string $event_name
	 */
	public function has_listeners( $event_name ): bool;

	/**
	 * Register an event subscriber with the dispatcher.
	 *
	 * @param  object|string $subscriber
	 * @return void
	 */
	public function subscribe( $subscriber );

	/**
	 * Dispatch an event and call the listeners.
	 *
	 * @param  string|object $event Event name.
	 * @param  mixed         $payload Event payload.
	 * @return mixed
	 */
	public function dispatch( $event, $payload = [] );

	/**
	 * Remove a set of listeners from the dispatcher.
	 *
	 * @param string          $event Event to remove.
	 * @param callable|string $listener Listener to remove.
	 * @param int             $priority Priority of the listener.
	 * @return void
	 */
	public function forget( $event, $listener = null, int $priority = 10 );
}
