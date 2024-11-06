<?php
/**
 * This file contains the MockAction class
 *
 * @package Mantle
 */

namespace Mantle\Testing;

/**
 * Helper class for testing code that involves actions and filters.
 *
 * Typical use: $ma = new MockAction(); add_action( 'foo', [ $ma, 'action' ] );
 *
 * @deprecated Used as a shim for core tests. New tests should use Mantle's
 * testing hook helpers {@see https://mantle.alley.com/docs/testing/hooks}.
 */
class Mock_Action {
	/**
	 * Events log. When a callback fires, it will log an event to this array.
	 *
	 * @var array
	 */
	public $events = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->reset();
	}

	/**
	 * Reset the events.
	 */
	public function reset(): void {
		$this->events = [];
	}

	/**
	 * Get the current or last-run filter.
	 *
	 * @return mixed|string
	 */
	public function current_filter() {
		if ( is_callable( 'current_filter' ) ) {
			return current_filter();
		}
		global $wp_actions;
		return end( $wp_actions );
	}

	/**
	 * Action callback.
	 *
	 * @param mixed ...$args Arguments passed to the callback.
	 * @return mixed First argument.
	 */
	public function action( ...$args ) {
		$this->events[] = [
			'action' => __FUNCTION__,
			'tag'    => $this->current_filter(),
			'args'   => $args,
		];
		return $args[0];
	}

	/**
	 * Filter callback.
	 *
	 * @param mixed ...$args Arguments passed to the callback.
	 * @return mixed First argument.
	 */
	public function filter( ...$args ) {
		$this->events[] = [
			'filter' => __FUNCTION__,
			'tag'    => $this->current_filter(),
			'args'   => $args,
		];
		return $args[0];
	}

	/**
	 * Filter callback for 'all'.
	 *
	 * @param string $tag     Action/filter tag.
	 * @param mixed  ...$args Arguments passed to the callback.
	 */
	public function filter_all( $tag, ...$args ): void {
		// This one doesn't return the result, so it's safe to use with the 'all' filter.
		$this->events[] = [
			'filter' => __FUNCTION__,
			'tag'    => $tag,
			'args'   => $args,
		];
	}

	/**
	 * Get the events (actions/filters, tags, args) that the mock has logged.
	 *
	 * @return array Events.
	 */
	public function get_events() {
		return $this->events;
	}

	/**
	 * Return a count of the number of times the action was called since the last
	 * reset.
	 *
	 * @param string $tag Optional. Action or filter tag. If absent, counts all
	 *                    events.
	 */
	public function get_call_count( $tag = '' ): int {
		if ( ! empty( $tag ) ) {
			$count = 0;
			foreach ( $this->events as $event ) {
				if ( $event['action'] === $tag ) {
					++$count;
				}
			}
			return $count;
		}
		return count( $this->events );
	}

	/**
	 * Return an array of the tags that triggered calls to this action.
	 *
	 * @return array
	 */
	public function get_tags() {
		return array_column( $this->events, 'tag' );
	}

	/**
	 * Return an array of args passed in calls to this action.
	 *
	 * @return array
	 */
	public function get_args() {
		return array_column( $this->events, 'args' );
	}
}
