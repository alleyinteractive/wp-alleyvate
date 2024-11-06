<?php
/**
 * This file contains the Utils class
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Support\Collection;
use Mantle\Support\Str;
use Mantle\Testing\Doubles\Spy_REST_Server;

use function Mantle\Support\Helpers\collect;
use function Termwind\render;

require_once __DIR__ . '/concerns/trait-output-messages.php';

/**
 * Assorted testing utilities.
 *
 * A fork of https://github.com/WordPress/wordpress-develop/blob/master/tests/phpunit/includes/utils.php.
 */
class Utils {
	use Concerns\Output_Messages;

	/**
	 * Default database name.
	 *
	 * @var string
	 */
	public const DEFAULT_DB_NAME = 'wordpress_unit_tests';

	/**
	 * Default database user.
	 *
	 * @var string
	 */
	public const DEFAULT_DB_USER = 'root';

	/**
	 * Default database password.
	 *
	 * @var string
	 */
	public const DEFAULT_DB_PASSWORD = 'root';

	/**
	 * Default database host.
	 *
	 * @var string
	 */
	public const DEFAULT_DB_HOST = 'localhost';

	/**
	 * Default permalink structure.
	 *
	 * @var string
	 */
	public const DEFAULT_PERMALINK_STRUCTURE = '/%year%/%monthnum%/%day%/%postname%/';

	/**
	 * Get the output from a given callable.
	 *
	 * @param callable $callable Callable to execute.
	 * @param array    $args     Arguments to pass to the callable.
	 * @return false|string Rendered output on success, false on failure.
	 */
	public static function get_echo( $callable, $args = [] ) {
		ob_start();
		call_user_func_array( $callable, $args );
		return ob_get_clean();
	}

	/**
	 * Unregister a post status.
	 *
	 * @param string $status Post status to unregister.
	 */
	public static function unregister_post_status( $status ): void {
		unset( $GLOBALS['wp_post_statuses'][ $status ] );
	}

	/**
	 * Remove WP query vars from the global space.
	 */
	public static function cleanup_query_vars(): void {
		// Clean out globals to stop them polluting wp and wp_query.
		foreach ( $GLOBALS['wp']->public_query_vars as $v ) {
			unset( $GLOBALS[ $v ] );
		}

		foreach ( $GLOBALS['wp']->private_query_vars as $v ) {
			unset( $GLOBALS[ $v ] );
		}

		foreach ( get_taxonomies( [], 'objects' ) as $t ) {
			if ( $t->publicly_queryable && ! empty( $t->query_var ) ) {
				$GLOBALS['wp']->add_query_var( $t->query_var );
			}
		}

		foreach ( get_post_types( [], 'objects' ) as $t ) {
			if ( is_post_type_viewable( $t ) && ! empty( $t->query_var ) ) {
				$GLOBALS['wp']->add_query_var( $t->query_var );
			}
		}
	}

	/**
	 * Reset `$_SERVER` variables
	 */
	public static function reset_server(): void {
		$_SERVER['HTTP_HOST']       = WP_TESTS_DOMAIN;
		$_SERVER['REMOTE_ADDR']     = '127.0.0.1'; // phpcs:ignore WordPressVIPMinimum.Variables
		$_SERVER['REQUEST_METHOD']  = 'GET';
		$_SERVER['REQUEST_URI']     = '';
		$_SERVER['SERVER_NAME']     = WP_TESTS_DOMAIN;
		$_SERVER['SERVER_PORT']     = '80';
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';

		unset( $_SERVER['HTTP_REFERER'] );
		unset( $_SERVER['HTTPS'] );
	}

	/**
	 * Use the Spy_REST_Server class for the REST server.
	 *
	 * @return string The server class name.
	 */
	public static function wp_rest_server_class_filter(): string {
		return Spy_REST_Server::class;
	}

	/**
	 * Deletes all data from the database.
	 */
	public static function delete_all_data(): void {
		// phpcs:disable WordPress.DB,WordPressVIPMinimum.Variables
		global $wpdb;

		foreach ( [
			$wpdb->posts,
			$wpdb->postmeta,
			$wpdb->comments,
			$wpdb->commentmeta,
			$wpdb->term_relationships,
			$wpdb->termmeta,
		] as $table ) {
			$wpdb->query( "DELETE FROM {$table}" );
		}

		foreach ( [
			$wpdb->terms,
			$wpdb->term_taxonomy,
		] as $table ) {
			$wpdb->query( "DELETE FROM {$table} WHERE term_id != 1" );
		}

		$wpdb->query( "UPDATE {$wpdb->term_taxonomy} SET count = 0" );

		$wpdb->query( "DELETE FROM {$wpdb->users} WHERE ID != 1" );
		$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE user_id != 1" );
		// phpcs:enable
	}

	/**
	 * Deletes all posts from the database.
	 */
	public static function delete_all_posts(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB
		$all_posts = $wpdb->get_results( "SELECT ID, post_type from {$wpdb->posts}", ARRAY_A );
		if ( ! $all_posts ) {
			return;
		}

		foreach ( $all_posts as $all_post ) {
			if ( 'attachment' === $all_post['post_type'] ) {
				wp_delete_attachment( $all_post['ID'], true );
			} else {
				wp_delete_post( $all_post['ID'], true );
			}
		}
	}

	/**
	 * Set a permalink structure.
	 *
	 * Hooked as a callback to the 'populate_options' action, we use this function to set a permalink structure during
	 * `wp_install()`, so that WP doesn't attempt to do a time-consuming remote request.
	 *
	 * @since 4.2.0
	 */
	public static function set_default_permalink_structure_for_tests(): void {
		update_option( 'permalink_structure', static::DEFAULT_PERMALINK_STRUCTURE );
	}

	/**
	 * Define the constants that are necessary for WordPress installation.
	 *
	 * Mirrors the wp-tests-config-sample.php file that can optionally be loaded
	 * before this file. Any constant that is already defined is not overridden
	 * with the option to use environment variables to override the default
	 * values.
	 */
	public static function setup_configuration(): void {
		global $table_prefix;

		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
		defined( 'ABSPATH' ) || define( 'ABSPATH', Str::trailing_slash( preg_replace( '#/wp-content/.*$#', '/', __DIR__ ) ) );
		defined( 'WP_DEBUG' ) || define( 'WP_DEBUG', true );

		defined( 'DB_NAME' ) || define( 'DB_NAME', static::env( 'WP_DB_NAME', static::DEFAULT_DB_NAME ) );
		defined( 'DB_USER' ) || define( 'DB_USER', static::env( 'WP_DB_USER', static::DEFAULT_DB_USER ) );
		defined( 'DB_PASSWORD' ) || define( 'DB_PASSWORD', static::env( 'WP_DB_PASSWORD', static::DEFAULT_DB_PASSWORD ) );
		defined( 'DB_HOST' ) || define( 'DB_HOST', static::env( 'WP_DB_HOST', static::DEFAULT_DB_HOST ) );
		defined( 'DB_CHARSET' ) || define( 'DB_CHARSET', static::ENV( 'WP_DB_CHARSET', 'utf8' ) );
		defined( 'DB_COLLATE' ) || define( 'DB_COLLATE', static::ENV( 'WP_DB_COLLATE', '' ) );

		defined( 'AUTH_KEY' ) || define( 'AUTH_KEY', 'put your unique phrase here' );
		defined( 'SECURE_AUTH_KEY' ) || define( 'SECURE_AUTH_KEY', 'put your unique phrase here' );
		defined( 'LOGGED_IN_KEY' ) || define( 'LOGGED_IN_KEY', 'put your unique phrase here' );
		defined( 'NONCE_KEY' ) || define( 'NONCE_KEY', 'put your unique phrase here' );
		defined( 'AUTH_SALT' ) || define( 'AUTH_SALT', 'put your unique phrase here' );
		defined( 'SECURE_AUTH_SALT' ) || define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
		defined( 'LOGGED_IN_SALT' ) || define( 'LOGGED_IN_SALT', 'put your unique phrase here' );
		defined( 'NONCE_SALT' ) || define( 'NONCE_SALT', 'put your unique phrase here' );

		$table_prefix = 'wptests_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		defined( 'WP_TESTS_DOMAIN' ) || define( 'WP_TESTS_DOMAIN', 'example.org' );
		defined( 'WP_TESTS_EMAIL' ) || define( 'WP_TESTS_EMAIL', 'admin@example.org' );
		defined( 'WP_TESTS_TITLE' ) || define( 'WP_TESTS_TITLE', 'Test Site' );
		defined( 'WP_PHP_BINARY' ) || define( 'WP_PHP_BINARY', 'php' );
		defined( 'WPLANG' ) || define( 'WPLANG', '' );

		// Setup the table prefix when running in parallel.
		if ( static::is_parallel() && $token = static::parallel_token() ) {
			$table_prefix .= "para_{$token}_"; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			if ( static::is_debug_mode() ) {
				static::info( "Using parallel table prefix: {$table_prefix}" );
			}
		}

		// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
	}

	/**
	 * Retrieve an environment variable with a fallback.
	 *
	 * @param string $variable Variable to get.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function env( string $variable, $default ) {
		$value = getenv( $variable );

		return false === $value ? $default : $value;
	}

	/**
	 * Retrieve an environment variable and check if it is truthy.
	 *
	 * @param string $variable Variable to get.
	 * @param bool   $default Default value used as a fallback.
	 */
	public static function env_bool( string $variable, bool $default ): bool {
		$value = static::env( $variable, $default );

		return in_array( strtolower( (string) $value ), [ 'true', '1', 'yes' ], true );
	}

	/**
	 * Ensure an environment variable is is a valid string that can be passed to
	 * to a shell script.
	 *
	 * For example, an empty string should be '' vs a blank space.
	 * `escapeshellarg()` doesn't fit here because the script is expecting
	 * unquoted arguments.
	 *
	 * @param string|bool $string String to sanitize.
	 */
	public static function shell_safe( string|bool $string ): string {
		if ( is_bool( $string ) ) {
			return $string ? 'true' : 'false';
		}

		return empty( trim( $string ) ) ? "''" : "\"{$string}\"";
	}

	/**
	 * Install a WordPress codebase through a shell script.
	 *
	 * This installs the WordPress codebase in the specified directory. It does
	 * not install the WordPress database.
	 *
	 * @param string $directory Directory to install WordPress in.
	 */
	public static function install_wordpress( string $directory ): void {
		$install_vip_mu_plugins = static::env_bool( 'MANTLE_INSTALL_VIP_MU_PLUGINS', false );
		$use_sqlite_db          = static::env_bool( 'MANTLE_USE_SQLITE', false );

		// Handle the legacy values for MANTLE_INSTALL_OBJECT_CACHE.
		if ( static::env_bool( 'MANTLE_INSTALL_OBJECT_CACHE', false ) ) {
			$install_object_cache = 'memcached';
		} else {
			$install_object_cache = static::env( 'MANTLE_INSTALL_OBJECT_CACHE', false );

			if ( $install_object_cache && ! in_array( $install_object_cache, [ 'memcached', 'redis' ], true ) ) {
				static::error( '🚨 Invalid value for MANTLE_INSTALL_OBJECT_CACHE (' . $install_object_cache . '). Ignoring...' );

				$install_object_cache = false;
			}
		}

		$branch = static::env( 'MANTLE_CI_BRANCH', 'HEAD' );

		// Compile the variables to pass to the shell script.
		$variables = collect(
			[
				[ 'WP_CORE_DIR', $directory ],
				[ 'WP_MULTISITE', static::env( 'WP_MULTISITE', '0' ) ],
			]
		)
				->when(
					$use_sqlite_db,
					fn ( Collection $collection ) => $collection->push( [ 'WP_USE_SQLITE', 'true' ] )
				)
				->when(
					static::is_debug_mode(),
					fn ( Collection $collection ) => $collection->push( [ 'INSTALL_WP_TEST_DEBUG', 'true' ] )
				)
				->when(
					! empty( static::env( 'CACHEDIR', '' ) ),
					fn ( Collection $collection ) => $collection->push( [ 'CACHEDIR', static::env( 'CACHEDIR', '' ) ] )
				)
				->map(
					fn ( array $item ) => sprintf( 'export %s=%s', $item[0], static::shell_safe( $item[1] ) )
				)
				->implode( ' && ' );

		$command = sprintf(
			'%s && curl -s %s | bash -s %s',
			$variables,
			"https://raw.githubusercontent.com/alleyinteractive/mantle-ci/{$branch}/install-wp-tests.sh",
			collect(
				[
					static::shell_safe( defined( 'DB_NAME' ) ? DB_NAME : static::env( 'WP_DB_NAME', static::DEFAULT_DB_NAME ) ),
					static::shell_safe( defined( 'DB_USER' ) ? DB_USER : static::env( 'WP_DB_USER', static::DEFAULT_DB_USER ) ),
					static::shell_safe( defined( 'DB_PASSWORD' ) ? DB_PASSWORD : static::env( 'WP_DB_PASSWORD', static::DEFAULT_DB_PASSWORD ) ),
					static::shell_safe( defined( 'DB_HOST' ) ? DB_HOST : static::env( 'WP_DB_HOST', static::DEFAULT_DB_HOST ) ),
					static::shell_safe( static::env( 'WP_VERSION', 'latest' ) ),
					static::shell_safe( static::env( 'WP_SKIP_DB_CREATE', 'false' ) ),
					static::shell_safe( $install_vip_mu_plugins ? 'true' : 'false' ),
					static::shell_safe( $install_object_cache ),
				]
			)->implode( ' ' ),
		);

		$retval = 0;
		$output = static::command( $command, $retval );

		if ( 0 !== $retval ) {
			static::error( '🚨 Error installing WordPress! Output from installation:', 'Install Rsync' );
			static::code( $output );
			exit( 1 );
		}
	}

	/**
	 * Install a plugin to a WordPress codebase through a shell script.
	 *
	 * @param string $directory Directory to install WordPress in.
	 * @param string $plugin Plugin slug to install.
	 * @param string $version_or_url Plugin version to install OR URL to install from.
	 */
	public static function install_plugin( string $directory, string $plugin, string $version_or_url = 'latest' ): void {
		$branch = static::env( 'MANTLE_CI_BRANCH', 'HEAD' );

		// Compile the variables to pass to the shell script.
		$variables = collect(
			[
				[ 'WP_CORE_DIR', $directory ],
			]
		)
			->when(
				! empty( static::env( 'CACHEDIR', '' ) ),
				fn ( Collection $collection ) => $collection->push( [ 'CACHEDIR', static::env( 'CACHEDIR', '' ) ] )
			)
			->map(
				fn ( array $item ) => sprintf( 'export %s=%s', $item[0], static::shell_safe( $item[1] ) )
			)
			->implode( ' && ' );

		$command = sprintf(
			'%s && curl -s %s | bash -s %s',
			$variables,
			"https://raw.githubusercontent.com/alleyinteractive/mantle-ci/{$branch}/install-plugin.sh",
			collect(
				[
					$plugin,
					$version_or_url,
				]
			)->implode( ' ' ),
		);

		$retval = 0;
		$output = static::command( $command, $retval );

		if ( 0 !== $retval ) {
			static::error( '🚨 Error installing WordPress! Output from installation:', 'Install Rsync' );
			static::code( $output );
			exit( 1 );
		}
	}

	/**
	 * Check if the command is being run in debug mode.
	 */
	public static function is_debug_mode(): bool {
		if ( defined( 'MANTLE_TESTING_DEBUG' ) && MANTLE_TESTING_DEBUG ) {
			return true;
		}

		return ! empty(
			array_intersect(
				(array) ( $_SERVER['argv'] ?? [] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				[
					'--debug',
					'--verbose',
					'-v',
				],
			)
		);
	}

	/**
	 * Run a system command and return the output.
	 *
	 * @param string|string[] $command Command to run.
	 * @param int             $exit_code Exit code.
	 * @return string[]
	 */
	public static function command( $command, &$exit_code = null ) {
		$is_debug_mode = static::is_debug_mode();

		// Display the command if in debug mode.
		if ( $is_debug_mode ) {
			$time = microtime( true );

			render(
				'<div class="p-1">
					Running:
					<code>' . implode( ' ', (array) $command ) . '</code>
				</div>'
			);
		}

		if ( is_array( $command ) ) {
			$command = implode( ' ', $command );
		}

		$output = null;

		exec( $command, $output, $exit_code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec

		// Display the command runtime if in debug mode.
		if ( $is_debug_mode ) {
			$time = microtime( true ) - $time;

			render(
				'<div class="p-1">
					Finished in ' . number_format( $time, 2 ) . 's with exit code ' . $exit_code . '.
				</div>'
			);
		}

		return $output;
	}

	/**
	 * Ensure that Composer is loaded for the current environment.
	 */
	public static function ensure_composer_loaded(): void {
		$paths = [
			preg_replace( '#/vendor/.*$#', '/vendor/autoload.php', __DIR__ ),
			__DIR__ . '/../../../vendor/autoload.php',
			__DIR__ . '/../../vendor/autoload.php',
		];

		foreach ( $paths as $path ) {
			if ( ! is_dir( $path ) && file_exists( $path ) ) {
				require_once $path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

				return;
			}
		}
	}

	/**
	 * Register a shutdown function to handle errors.
	 *
	 * Used during the WordPress installation process to catch silent errors.
	 */
	public static function register_shutdown_function(): void {
		register_shutdown_function( [ static::class, 'handle_shutdown' ] );
	}

	/**
	 * Handle a shutdown error and display it.
	 */
	public static function handle_shutdown(): void {
		$error = error_get_last();

		if ( ! $error ) {
			return;
		}

		static::error( '🚨 Error during test run:', 'Shutdown' );
		static::code( $error );

		exit( 1 );
	}

	/**
	 * Check if the current test run is parallel with paratest.
	 */
	public static function is_parallel(): bool {
		return ! empty( static::parallel_token() );
	}

	/**
	 * Retrieve the parallel token for the current test run.
	 *
	 * @return string
	 */
	public static function parallel_token(): ?string {
		return static::env( 'TEST_TOKEN', null );
	}

	/**
	 * Check if the current test run is the paratest bootstrap.
	 *
	 * The parallel token will not be set in the initial bootstrap.
	 */
	public static function is_parallel_bootstrap(): bool {
		return empty( static::parallel_token() ) && isset( $_SERVER['SCRIPT_NAME'] ) && str_contains( (string) $_SERVER['SCRIPT_NAME'], 'paratest' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}
}
