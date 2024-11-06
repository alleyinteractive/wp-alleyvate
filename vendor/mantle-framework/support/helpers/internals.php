<?php
/**
 * Internal functions for helpers.
 *
 * @package Mantle
 */

namespace Mantle\Support\Helpers;

/**
 * Mark a hook as incorrectly removed.
 *
 * Internals are not subject to semantic-versioning constraints.
 *
 * @param array $args Array with the arguments for {@see remove_filter()}.
 */
function invalid_hook_removal( $args ): void {
	// PHPCS does not recognize the [ $arg1, $arg2 ] syntax.
	[$hook, $callable] = $args;

	$function_name = get_callable_fqn( $callable );

	if ( $function_name ) {
		\_doing_it_wrong(
			__FUNCTION__,
			\esc_html(
				\sprintf(
					/* translators: 1: function name, 2: hook name */
					\__( 'Failed to remove "%1$s" from %2$s!', 'mantle' ),
					$function_name,
					$hook
				)
			),
			'',
		);
	}

	if ( ! $function_name ) {
		\_doing_it_wrong(
			__FUNCTION__,
			\esc_html(
				\sprintf(
					/* translators: 1: hook name */
					\__( 'Failed to remove function from %1$s!', 'mantle' ),
					$hook
				)
			),
			'',
		);
	}
}
