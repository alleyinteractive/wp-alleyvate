<?php
/**
 * This file contains the WP_Die helper class
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Testing\Exceptions\WP_Die_Exception;

/**
 * WP_Die helpers.
 */
class WP_Die {

	/**
	 * Retrieves the `wp_die()` handler.
	 *
	 * @return callable The test die handler.
	 */
	public static function get_handler() {
		return [ static::class, 'handler' ];
	}

	/**
	 * Returns the die handler.
	 *
	 * @return callable The die handler.
	 */
	public static function get_toggled_handler() {
		return [ static::class, 'toggled_handler' ];
	}

	/**
	 * Returns the die handler.
	 *
	 * @return callable The die handler.
	 */
	public static function get_exit_handler() {
		return [ static::class, 'exit_handler' ];
	}

	/**
	 * Handles the WP die handler by outputting the given values as text.
	 *
	 * @param string $message The message.
	 * @param string $title   The title.
	 * @param array  $args    Array with arguments.
	 */
	public static function toggled_handler( $message, $title = '', $args = [] ): void {
		if ( ! $GLOBALS['_wp_die_disabled'] ) {
			static::txt_handler( $message, $title, $args );
		}
	}

	/**
	 * Throws an exception when called.
	 *
	 * @throws WP_Die_Exception Exception containing the message.
	 *
	 * @param string|\WP_Error $message The `wp_die()` message.
	 */
	public static function handler( $message ): void {
		if ( is_wp_error( $message ) ) {
			$message = $message->get_error_message();
		}

		if ( ! is_scalar( $message ) ) {
			$message = '0';
		}

		throw new WP_Die_Exception( $message );
	}

	/**
	 * Dies without an exit.
	 *
	 * @param string|\WP_Error $message The message.
	 * @param string           $title   The title.
	 * @param array            $args    Array with arguments.
	 */
	public static function txt_handler( $message, $title, $args ): void {
		// phpcs:disable WordPress.Security.EscapeOutput

		// Display fatal error information from wp_die().
		if ( is_wp_error( $message ) && 'internal_server_error' === $message->get_error_code() ) {
			$error = $message->get_error_data( 'internal_server_error' )['error'] ?? [];

			if ( ! empty( $error ) ) {

				echo "\nwp_die called\n";
				echo "Internal server error: {$error['message']}\n\n";

				if ( ! empty( $error['file'] ) ) {
					echo "File: {$error['file']}\n";
				}

				if ( ! empty( $error['line'] ) ) {
					echo "Line: {$error['line']}\n";
				}

				return;
			}
		}

		[ $message, $title, $args ] = _wp_die_process_input( $message, $title, $args );

		echo "\nwp_die called\n";
		echo "Message : $message\n";
		echo "Title : $title\n";
		if ( ! empty( $args ) ) {
			echo "Args: \n";
			foreach ( $args as $k => $v ) {
				if ( is_array( $v ) ) {
					continue;
				}

				echo "\t $k : $v\n";
			}
		}

		// Provide a helper message for database errors after displaying the error message.
		if ( false !== strpos( (string) $message, 'database' ) ) {
			echo "\n\n";
			printf(
				"\033[31m%s \033[0m\n\n",
				'🚨 It looks like there was an error connecting to the database. Mantle can help!',
			);

			$path = trailingslashit( ABSPATH ) . 'wp-tests-config.php';

			// Display debug information if wp-tests-config.php doesn't exist.
			if ( ! file_exists( $path ) ) {
				echo "Try creating a \033[36mwp-tests-config.php\033[0m file in your project root.\n\n";
				echo "Mantle can help with that. Either run `wp mantle test-config` to generate a configuration or download the latest\n";
				echo "copy to the project root to \033[36m{$path}\033[0m: \n\n";
				echo "    \033[33mhttps://raw.githubusercontent.com/alleyinteractive/mantle-framework/HEAD/src/mantle/testing/wp-tests-config-sample.php\033[0m \n\n";
				echo "    \033[33mwget https://raw.githubusercontent.com/alleyinteractive/mantle-framework/HEAD/src/mantle/testing/wp-tests-config-sample.php -O $path\033[0m \n\n";

				echo "Mantle can run without a configuration in place but assumes a default set of configuration.\n";
				echo "🔍 Check if your database is configured to allow access with the default credentials:\n\n";

				echo "DB_NAME: \033[36m" . Utils::env( 'WP_DB_NAME', Utils::DEFAULT_DB_NAME ) . "\033[0m\n";
				echo "DB_USER: \033[36m" . Utils::env( 'WP_DB_USER', Utils::DEFAULT_DB_USER ) . "\033[0m\n";
				echo "DB_PASSWORD: \033[36m" . Utils::env( 'WP_DB_PASSWORD', Utils::DEFAULT_DB_PASSWORD ) . "\033[0m\n";
				echo "DB_HOST: \033[36m" . Utils::env( 'WP_DB_HOST', Utils::DEFAULT_DB_HOST ) . "\033[0m\n";

				echo "\n";
			} else {
				echo "🔍 Check the configuration settings in \033[36m{$path}\033[0m and ensure you have access to the configured database.\n\n";
			}
		}

		// phpcs:enable
	}

	/**
	 * Dies with an exit.
	 *
	 * @param string $message The message.
	 * @param string $title   The title.
	 * @param array  $args    Array with arguments.
	 */
	public static function exit_handler( $message, $title, $args ): void {
		// phpcs:disable WordPress.Security.EscapeOutput
		echo "\nwp_die called\n";
		echo "Message : $message\n";
		echo "Title : $title\n";
		if ( ! empty( $args ) ) {
			echo "Args: \n";
			foreach ( $args as $k => $v ) {
				echo "\t $k : $v\n";
			}
		}
		exit( 1 );
		// phpcs:enable
	}

	/**
	 * Disables the WP die handler.
	 */
	public static function disable(): void {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		$GLOBALS['_wp_die_disabled'] = true;
	}

	/**
	 * Enables the WP die handler.
	 */
	public static function enable(): void {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		$GLOBALS['_wp_die_disabled'] = false;
	}
}
