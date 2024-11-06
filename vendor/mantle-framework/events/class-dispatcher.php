<?php
/**
 * Dispatcher class file.
 *
 * @package Mantle
 *
 * @phpcs:disable Squiz.Commenting.FunctionComment
 */

namespace Mantle\Events;

use Closure;
use Mantle\Container\Container;
use Mantle\Contracts\Events\Dispatcher as Dispatcher_Contract;
use Mantle\Support\Arr;
use Mantle\Support\Str;

/**
 * Event Dispatcher
 *
 * @todo Add queued event listeners.
 * @todo Add wildcard listeners.
 */
class Dispatcher implements Dispatcher_Contract {
	use WordPress_Action;

	/**
	 * The IoC container instance.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * The queue resolver instance.
	 *
	 * @var callable
	 */
	protected $queue_resolver;

	/**
	 * Create a new event dispatcher instance.
	 *
	 * @param Container|null $container Container instance.
	 */
	public function __construct( Container $container = null ) {
		$this->container = $container ?: new Container();
	}

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @todo Add wildcard listeners.
	 *
	 * @param string|array $events Event(s) to listen to.
	 * @param mixed        $listener Listener to register.
	 * @param int          $priority Event priority.
	 * @param  \Closure|string $listener Listener callback.
	 */
	public function listen( $events, $listener, int $priority = 10 ): void {
		foreach ( (array) $events as $event ) {
			add_action(
				$event,
				$this->make_listener( $listener ),
				$priority,
				PHP_INT_MAX,
			);
		}
	}

	/**
	 * Determine if a given event has listeners.
	 *
	 * @param  string $event_name Event name.
	 */
	public function has_listeners( $event_name ): bool {
		return has_filter( $event_name );
	}

	/**
	 * Register an event subscriber with the dispatcher.
	 *
	 * @param  object|string $subscriber
	 */
	public function subscribe( $subscriber ): void {
		$subscriber = $this->resolve_subscriber( $subscriber );

		$subscriber->subscribe( $this );
	}

	/**
	 * Resolve the subscriber instance.
	 *
	 * @param  object|string $subscriber
	 * @return mixed
	 */
	protected function resolve_subscriber( $subscriber ) {
		if ( is_string( $subscriber ) ) {
			return $this->container->make( $subscriber );
		}

		return $subscriber;
	}

	/**
	 * Fire an event and call the listeners.
	 *
	 * @todo Break out support for a filter.
	 *
	 * @param  string|object $event Event name.
	 * @param  mixed         $payload Event payload.
	 * @return mixed
	 */
	public function dispatch( $event, $payload = [ null ] ) {
		[ $event, $payload ] = $this->parse_event_and_payload( $event, $payload );

		if ( function_exists( 'apply_filters' ) ) {
			return apply_filters( $event, ...$payload ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		}

		return null;
	}

	/**
	 * Parse the given event and payload and prepare them for dispatching.
	 *
	 * @param  mixed $event
	 * @param  mixed $payload
	 * @return array
	 */
	protected function parse_event_and_payload( $event, $payload ) {
		if ( is_object( $event ) ) {
			[ $payload, $event ] = [ [ $event ], $event::class ];
		}

		return [ $event, Arr::wrap( $payload ) ];
	}

	/**
	 * Get all of the listeners for a given event name.
	 *
	 * @param  string $event_name
	 * @return array
	 */
	public function get_listeners( $event_name ) {
		$listeners = $this->listeners[ $event_name ] ?? [];

		return class_exists( $event_name, false )
			? $this->add_interface_listeners( $event_name, $listeners )
			: $listeners;
	}

	/**
	 * Add the listeners for the event's interfaces to the given array.
	 *
	 * @param  string $event_name
	 * @param  array  $listeners
	 * @return array
	 */
	protected function add_interface_listeners( $event_name, array $listeners = [] ) {
		foreach ( class_implements( $event_name ) as $interface ) {
			if ( isset( $this->listeners[ $interface ] ) ) {
				foreach ( $this->listeners[ $interface ] as $names ) {
					$listeners = array_merge( $listeners, (array) $names );
				}
			}
		}

		return $listeners;
	}

	/**
	 * Register an event listener with the dispatcher.
	 *
	 * @param  \Closure|string $listener
	 */
	public function make_listener( $listener ): Closure {
		if ( is_string( $listener ) ) {
			return $this->create_class_listener( $listener );
		}

		return fn ( ...$payload ) => $this->create_action_callback(
			$listener,
		)( ...array_values( $payload ) );
	}

	/**
	 * Create a class based listener using the IoC container.
	 *
	 * @param  string $listener
	 */
	public function create_class_listener( $listener ): Closure {
		return function ( ...$payload ) use ( $listener ) {
			$callable = $this->create_action_callback(
				$this->create_class_callable( $listener ),
			);

			return $callable( ...array_values( $payload ) );
		};
	}

	/**
	 * Create the class based event callable.
	 *
	 * @param  string $listener
	 * @return callable
	 */
	protected function create_class_callable( $listener ) {
		[ $class, $method ] = $this->parse_class_callable( $listener );

		// todo: add queued callback support.

		return [ $this->container->make( $class ), $method ];
	}

	/**
	 * Parse the class listener into class and method.
	 *
	 * @param  string $listener
	 * @return array
	 */
	protected function parse_class_callable( $listener ) {
		return Str::parse_callback( $listener, 'handle' );
	}

	/**
	 * Remove a set of listeners from the dispatcher.
	 *
	 * @param string|object $event Event to remove.
	 * @param callable|string $listener Listener to remove.
	 * @param int $priority Priority of the listener.
	 */
	public function forget( $event, $listener = null, int $priority = 10 ): void {
		if ( is_object( $event ) ) {
			$event = $event::class;
		}

		if ( null === $listener ) {
			remove_all_filters( $event, $priority );
		} else {
			remove_filter( $event, $listener, $priority );
		}
	}
}
