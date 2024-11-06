<?php
/**
 * Route_Group trait file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing\Concerns;

use Mantle\Http\Routing\Route;
use Mantle\Support\Arr;

/**
 * Route Group functions.
 */
trait Route_Group {
	/**
	 * The route group attribute stack.
	 *
	 * @var array
	 */
	protected $group_stack = [];

	/**
	 * Determine if the router currently has a group stack.
	 *
	 * @return bool
	 */
	public function has_group_stack() {
		return ! empty( $this->group_stack );
	}

	/**
	 * Get the current group stack for the router.
	 *
	 * @return array
	 */
	public function get_group_stack() {
		return $this->group_stack;
	}

	/**
	 * Create a route group with shared attributes.
	 *
	 * @param  array           $attributes
	 * @param  \Closure|string $routes
	 */
	public function group( array $attributes, $routes ): void {
		$this->update_group_stack( $attributes );

		// Once we have updated the group stack, we'll load the provided routes and
		// merge in the group's attributes when the routes are created. After we
		// have created the routes, we will pop the attributes off the stack.
		$this->load_routes( $routes );

		array_pop( $this->group_stack );
	}

	/**
	 * Update the group stack with the given attributes.
	 *
	 * @param  array $attributes
	 * @return void
	 */
	protected function update_group_stack( array $attributes ) {
		if ( $this->has_group_stack() ) {
			$attributes = $this->merge_with_last_group( $attributes );
		}

		$this->group_stack[] = $attributes;
	}

	/**
	 * Merge the given array with the last group stack.
	 *
	 * @param array $new New route attributes.
	 * @param bool  $prepend_existing_prefix Prepend the existing prefix.
	 */
	public function merge_with_last_group( $new, $prepend_existing_prefix = true ): array {
		return static::merge( $new, end( $this->group_stack ), $prepend_existing_prefix );
	}

	/**
	 * Get the prefix from the last group on the stack.
	 */
	protected function get_last_group_prefix(): string {
		if ( $this->has_group_stack() ) {
			$last = end( $this->group_stack );

			return $last['prefix'] ?? '';
		}

		return '';
	}

	/**
	 * Merge the group stack with the controller action.
	 *
	 * @param Route $route Route instance.
	 */
	protected function merge_group_attributes_into_route( Route $route ): void {
		$route->set_action(
			$this->merge_with_last_group(
				$route->get_action(),
				false
			)
		);
	}

	/**
	 * Merge route groups into a new array.
	 *
	 * @param  array $new
	 * @param  array $old
	 * @param  bool  $prepend_existing_prefix
	 */
	public static function merge( $new, $old, $prepend_existing_prefix = true ): array {
		if ( isset( $new['domain'] ) ) {
			unset( $old['domain'] );
		}

		$new = array_merge(
			static::format_as( $new, $old ),
			[
				'namespace' => static::format_namespace( $new, $old ),
				'prefix'    => static::format_prefix( $new, $old, $prepend_existing_prefix ),
				'where'     => static::format_where( $new, $old ),
			]
		);

		return array_merge_recursive(
			Arr::except(
				$old,
				[ 'namespace', 'prefix', 'where', 'as' ]
			),
			$new
		);
	}

	/**
	 * Format the namespace for the new group attributes.
	 *
	 * @param  array $new
	 * @param  array $old
	 * @return string|null
	 */
	protected static function format_namespace( $new, $old ) {
		if ( isset( $new['namespace'] ) ) {
			return isset( $old['namespace'] ) && ! str_starts_with( (string) $new['namespace'], '\\' )
					? trim( (string) $old['namespace'], '\\' ) . '\\' . trim( (string) $new['namespace'], '\\' )
					: trim( (string) $new['namespace'], '\\' );
		}

		return $old['namespace'] ?? null;
	}

	/**
	 * Format the prefix for the new group attributes.
	 *
	 * @param  array $new
	 * @param  array $old
	 * @param  bool  $prepend_existing_prefix
	 * @return string|null
	 */
	protected static function format_prefix( $new, $old, $prepend_existing_prefix = true ) {
		$old = $old['prefix'] ?? null;

		if ( $prepend_existing_prefix ) {
			return isset( $new['prefix'] ) ? trim( (string) $old, '/' ) . '/' . trim( (string) $new['prefix'], '/' ) : $old;
		} else {
			return isset( $new['prefix'] ) ? trim( (string) $new['prefix'], '/' ) . '/' . trim( (string) $old, '/' ) : $old;
		}
	}

	/**
	 * Format the "wheres" for the new group attributes.
	 *
	 * @param  array $new
	 * @param  array $old
	 * @return array
	 */
	protected static function format_where( $new, $old ) {
		return array_merge(
			$old['where'] ?? [],
			$new['where'] ?? []
		);
	}

	/**
	 * Format the "as" clause of the new group attributes.
	 *
	 * @param  array $new
	 * @param  array $old
	 * @return array
	 */
	protected static function format_as( $new, $old ) {
		if ( isset( $old['as'] ) ) {
			$new['as'] = $old['as'] . ( $new['as'] ?? '' );
		}

		return $new;
	}
}
