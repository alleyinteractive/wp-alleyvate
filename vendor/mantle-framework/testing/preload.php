<?php
/**
 * Unit tests preload
 *
 * @package Mantle
 */

namespace Mantle\Testing;

/**
 * Adds hooks before loading WP.
 *
 * @see add_filter()
 *
 * @param string   $tag             The name of the filter to hook the $function_to_add callback to.
 * @param callable $function_to_add The callback to be run when the filter is applied.
 * @param int      $priority        Optional. Used to specify the order in which the functions
 *                                  associated with a particular action are executed.
 *                                  Lower numbers correspond with earlier execution,
 *                                  and functions with the same priority are executed
 *                                  in the order in which they were added to the action. Default 10.
 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
 * @return true
 */
function tests_add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ): bool {
	global $wp_filter;

	if ( function_exists( 'add_filter' ) ) {
		add_filter( $tag, $function_to_add, $priority, $accepted_args );
	} else {
		$idx = _test_filter_build_unique_id( $tag, $function_to_add );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$wp_filter[ $tag ][ $priority ][ $idx ] = [
			'function'      => $function_to_add,
			'accepted_args' => $accepted_args,
		];
	}
	return true;
}

/**
 * Generates a unique function ID based on the given arguments.
 *
 * @see _wp_filter_build_unique_id()
 *
 * @param string   $tag      Unused. The name of the filter to build ID for.
 * @param callable $function The function to generate ID for.
 * @return string Unique function ID for usage as array key.
 */
function _test_filter_build_unique_id( $tag, $function ) {
	if ( is_string( $function ) ) {
		return $function;
	}

	if ( is_object( $function ) ) {
		// Closures are currently implemented as objects.
		$function = [ $function, '' ];
	} else {
		$function = (array) $function;
	}

	if ( is_object( $function[0] ) ) {
		// Object class calling.
		return spl_object_hash( $function[0] ) . $function[1];
	} elseif ( is_string( $function[0] ) ) {
		// Static calling.
		return $function[0] . '::' . $function[1];
	}
}
