<?php
/**
 * Functions from the core unit test library that help with testing.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,
 *
 * @package Mantle
 */

if ( ! function_exists( 'rand_str' ) ) :
	/**
	 * Provide a random string.
	 *
	 * @param int $len String length.
	 */
	function rand_str( $len = 32 ): string {
		return substr( md5( uniqid( (string) wp_rand() ) ), 0, $len );
	}
endif;

if ( ! function_exists( 'rand_long_str' ) ) :
	/**
	 * Returns a string of the required length containing random characters.
	 *
	 * @param int $length The required length.
	 * @return string The string.
	 */
	function rand_long_str( $length ) {
		$chars  = 'abcdefghijklmnopqrstuvwxyz';
		$string = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$rand    = random_int( 0, strlen( $chars ) - 1 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand
			$string .= substr( $chars, $rand, 1 );
		}

		return $string;
	}
endif;

if ( ! function_exists( 'strip_ws' ) ) :
	/**
	 * Strips leading and trailing whitespace from each line in the string.
	 *
	 * @param string $txt The text.
	 * @return string Text with line-leading and line-trailing whitespace stripped.
	 */
	function strip_ws( $txt ) {
		$lines  = explode( "\n", $txt );
		$result = [];
		foreach ( $lines as $line ) {
			if ( trim( $line ) ) {
				$result[] = trim( $line );
			}
		}

		return trim( implode( "\n", $result ) );
	}
endif;

if ( ! function_exists( 'ensure_trailingslash' ) ) :
	/**
	 * Appends a trailing slash.
	 *
	 * @param string $string String to append a trailing slash to.
	 * @return string
	 */
	function ensure_trailingslash( $string ) {
		return remove_trailingslash( $string ) . '/';
	}
endif;

if ( ! function_exists( 'remove_trailingslash' ) ) :
	/**
	 * Removes trailing forward slashes and backslashes if they exist.
	 *
	 * @param string $string What to remove the trailing slashes from.
	 * @return string String without the trailing slashes.
	 */
	function remove_trailingslash( $string ) {
		return rtrim( $string, '/\\' );
	}
endif;

if ( ! function_exists( 'tests_add_filter' ) ) :
	/**
	 * Polyfill for providing a root namespace tests_add_filter() function for
	 * easier transition to the testing framework.
	 *
	 * @see \Mantle\Testing\tests_add_filter()
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
	function tests_add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		return \Mantle\Testing\tests_add_filter( $tag, $function_to_add, $priority, $accepted_args );
	}
endif;
