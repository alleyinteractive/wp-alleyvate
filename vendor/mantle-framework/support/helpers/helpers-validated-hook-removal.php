<?php
/**
 * Validated hook removal functions
 *
 * @package Mantle
 */

namespace Mantle\Support\Helpers;

/**
 * Remove a function from a filter, and trigger a notice if removal fails.
 *
 * @param mixed ...$args Arguments for {@see remove_filter()}.
 * @return bool Whether the function was removed.
 */
function remove_filter_validated( ...$args ) {
	$result = \remove_filter( ...$args );

	if ( false === $result ) {
		invalid_hook_removal( $args );
	}

	return $result;
}

/**
 * Remove a function from an action, and trigger a notice if removal fails.
 *
 * @param mixed ...$args Arguments for {@see remove_action()}.
 * @return bool Whether the function was removed.
 */
function remove_action_validated( ...$args ) {
	$result = \remove_action( ...$args );

	if ( false === $result ) {
		invalid_hook_removal( $args );
	}

	return $result;
}
