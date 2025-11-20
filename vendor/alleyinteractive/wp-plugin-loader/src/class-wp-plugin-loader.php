<?php
/**
 * WP_Plugin_Loader class file
 *
 * @package wp-plugin-loader
 */

namespace Alley\WP;

/**
 * WordPress Plugin Loader
 */
class WP_Plugin_Loader {
	/**
	 * Cache prefix for APCu caching.
	 *
	 * @var string|null
	 */
	protected ?string $cache_prefix = null;

	/**
	 * Array of loaded plugins.
	 *
	 * @var array<int, string>
	 */
	protected array $loaded_plugins = [];

	/**
	 * Flag to prevent any plugin activations for non-code activated plugins.
	 *
	 * @var bool
	 */
	protected bool $prevent_activations = false;

	/**
	 * Constructor.
	 *
	 * @param array<int, string> $plugins Array of plugins to load.
	 * @param string|bool        $cache Whether to enable caching with an optional prefix.
	 */
	public function __construct( public array $plugins = [], string|bool $cache = false ) {
		if ( did_action( 'plugins_loaded' ) ) {
			trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				'WP_Plugin_Loader should be instantiated before the plugins_loaded hook.',
				E_USER_WARNING
			);
		}

		if ( $cache ) {
			$this->enable_caching( true === $cache ? null : (string) $cache );
		}

		$this->load_plugins();

		add_filter( 'plugin_action_links', [ $this, 'filter_plugin_action_links' ], 10, 2 );
		add_filter( 'option_active_plugins', [ $this, 'filter_option_active_plugins' ] );
		add_filter( 'pre_update_option_active_plugins', [ $this, 'filter_pre_update_option_active_plugins' ] );
		add_filter( 'map_meta_cap', [ $this, 'prevent_plugin_activation' ], 10, 2 );
	}

	/**
	 * Prevent any plugin activations for non-code activated plugins.
	 *
	 * @param bool $prevent Whether to prevent activations.
	 * @return static
	 */
	public function prevent_activations( bool $prevent = true ): static {
		$this->prevent_activations = $prevent;

		return $this;
	}

	/**
	 * Enable APCu caching for plugin paths.
	 *
	 * @param string $prefix The cache prefix, defaults to 'wp-plugin-loader-'.
	 * @return static
	 */
	public function enable_caching( ?string $prefix = null ): static {
		return $this->set_cache_prefix( $prefix ?? 'wpl-' . basename( ABSPATH ) . '-' );
	}

	/**
	 * Set the cache prefix for APCu caching.
	 *
	 * @param string|null $prefix The cache prefix.
	 * @return static
	 */
	public function set_cache_prefix( ?string $prefix ): static {
		$this->cache_prefix = function_exists( 'apcu_fetch' ) && filter_var( ini_get( 'apc.enabled' ), FILTER_VALIDATE_BOOLEAN )
			? $prefix
			: null;

		return $this;
	}

	/**
	 * Load the requested plugins.
	 */
	protected function load_plugins(): void {
		$folders = [
			WP_PLUGIN_DIR,
			defined( 'WPCOM_VIP_CLIENT_MU_PLUGIN_DIR' ) ? WPCOM_VIP_CLIENT_MU_PLUGIN_DIR : WP_CONTENT_DIR . '/client-mu-plugins',
		];

		// Include the mu-plugins directory if it exists and we're not on a
		// WordPress VIP environment.
		if ( is_dir( WPMU_PLUGIN_DIR ) && ( ! defined( 'WPCOM_IS_VIP_ENV' ) || ! WPCOM_IS_VIP_ENV ) ) {
			$folders[] = WPMU_PLUGIN_DIR;
		}

		$folders = array_filter( $folders, 'is_dir' );

		// Loop through each plugin and attempt to load it.
		foreach ( $this->plugins as $plugin ) {
			$is_file = str_ends_with( $plugin, '.php' );

			// If the plugin is a potential file, loop through each possible
			// folder and attempt to load the plugin from it.
			if ( $is_file ) {
				foreach ( $folders as $folder ) {
					if ( file_exists( "$folder/$plugin" ) && ! is_dir( "$folder/$plugin" ) ) {
						$this->handle_plugin_path( "$folder/$plugin" );

						continue 2;
					}
				}
			} else {
				// Attempt to locate the plugin by name if it isn't a file.
				$sanitized_plugin = $this->sanitize_plugin_name( $plugin );

				// Check the APCu cache if we have a prefix set.
				if ( $this->cache_prefix ) {
					$cached_plugin_path = apcu_fetch( $this->cache_prefix . $sanitized_plugin );

					if ( false !== $cached_plugin_path ) {
						// Check if the plugin path is valid. If it is, require
						// it. Continue either way if the cache was not false.
						if ( is_string( $cached_plugin_path ) && ! empty( $cached_plugin_path ) ) {
							$this->handle_plugin_path( $cached_plugin_path );
						}

						continue;
					}
				}

				// Attempt to locate the plugin by name if it isn't a file.
				// Compile a list of possible paths to check for the plugin.
				$paths = [];

				foreach ( $folders as $folder ) {
					$paths[] = "$folder/$sanitized_plugin/$sanitized_plugin.php";
					$paths[] = "$folder/$sanitized_plugin/plugin.php";
					$paths[] = "$folder/$sanitized_plugin.php";

					if ( 0 === strpos( $sanitized_plugin, 'wordpress-' ) ) {
						$paths[] = "$folder/" . substr( $sanitized_plugin, 10 ) . "/$sanitized_plugin.php";
					} elseif ( 0 === strpos( $sanitized_plugin, 'wp-' ) ) {
						$paths[] = "$folder/" . substr( $sanitized_plugin, 3 ) . "/$sanitized_plugin.php";
					}

					// Plugin-specific exceptions that don't follow the standard pattern.
					$paths = array_merge(
						$paths,
						(array) match ( $sanitized_plugin ) {
							'logger' => [ "$folder/logger/ai-logger.php" ],
							'shortcake' => [ "$folder/shortcake/dev.php" ],
							'vip-decoupled-bundle' => [ "$folder/vip-decoupled-bundle/vip-decoupled.php" ],
							'wp-updates-notifier' => [ "$folder/wp-updates-notifier/class-sc-wp-updates-notifier.php" ],
							default => [],
						},
					);
				}

				foreach ( $paths as $path ) {
					if ( file_exists( $path ) ) {
						$this->handle_plugin_path( $path );

						// Cache the plugin path in APCu if we have a prefix set.
						if ( $this->cache_prefix ) {
							apcu_store( $this->cache_prefix . $sanitized_plugin, $path );
						}

						continue 2;
					}
				}
			}

			$this->handle_missing_plugin( $plugin );
		}
	}

	/**
	 * Load a plugin by file path.
	 *
	 * @param string $path The path to the plugin file.
	 * @return void
	 */
	protected function handle_plugin_path( string $path ): void {
		require_once $path; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

		// Mark the plugin as loaded if it is in the /plugins directory.
		if ( 0 === strpos( $path, WP_PLUGIN_DIR ) ) {
			$this->loaded_plugins[] = trim( substr( $path, strlen( WP_PLUGIN_DIR ) + 1 ), '/' );
		}
	}

	/**
	 * Handle a missing plugin.
	 *
	 * @todo Change return type to never when 8.1 is required.
	 *
	 * @param string $plugin The plugin name passed to the loader.
	 * @return void
	 */
	protected function handle_missing_plugin( string $plugin ): void {
		$error_message = sprintf( 'WP Plugin Loader: Plugin %s not found.', $plugin );

		trigger_error( esc_html( $error_message ), E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error

		if ( extension_loaded( 'newrelic' ) && function_exists( 'newrelic_notice_error' ) ) {
			newrelic_notice_error( $error_message );
		}

		// Send a 500 status code and no-cache headers to prevent caching of the error message.
		if ( ! headers_sent() ) {
			status_header( 500 );
			nocache_headers();
		}

		echo esc_html( $error_message );
		exit( 1 );
	}

	/**
	 * Ensure code activated plugins are shown as such on core plugins screens
	 *
	 * @param  array<string, string> $actions The existing list of actions.
	 * @param  string                $plugin_file The path to the plugin file.
	 * @return array<string, string>
	 */
	public function filter_plugin_action_links( $actions, $plugin_file ): array {
		$screen = get_current_screen();

		if ( in_array( $plugin_file, $this->loaded_plugins, true ) ) {
			unset( $actions['activate'] );
			unset( $actions['deactivate'] );
			$actions['wp-plugin-loader-code-activated-plugin'] = __( 'Enabled via code', 'wp-plugin-loader' );

			if ( $screen && is_a( $screen, 'WP_Screen' ) && 'plugins' === $screen->id ) {
				unset( $actions['network_active'] );
			}
		} elseif ( $this->prevent_activations ) {
			unset( $actions['activate'] );
			unset( $actions['deactivate'] );
		}

		return $actions;
	}

	/**
	 * Filters the list of active plugins to include the ones we loaded via code.
	 *
	 * @param array<int, string> $value The existing list of active plugins.
	 * @return array<int, string>
	 */
	public function filter_option_active_plugins( $value ): array {
		if ( ! is_array( $value ) ) {
			$value = [];
		}

		$value = array_unique( array_merge( $value, $this->loaded_plugins ) );

		sort( $value );

		return $value;
	}

	/**
	 * Exclude code-active plugins from the database option.
	 *
	 * @param array<int, string> $value The saved list of active plugins.
	 * @return array<int, string>
	 */
	public function filter_pre_update_option_active_plugins( $value ) {
		if ( ! is_array( $value ) ) {
			$value = [];
		}

		$value = array_diff( $value, $this->loaded_plugins );

		sort( $value );

		return $value;
	}

	/**
	 * Helper function to sanitize plugin folder name.
	 *
	 * @param string $folder Folder name.
	 * @return string Sanitized folder name
	 */
	protected function sanitize_plugin_name( string $folder ): string {
		$folder = preg_replace( '#([^a-zA-Z0-9-_.]+)#', '', $folder );
		return str_replace( '..', '', (string) $folder ); // To prevent going up directories.
	}

	/**
	 * Prevent any plugin activations for non-code activated plugins.
	 *
	 * @param array<string> $caps Array of capabilities.
	 * @param string        $cap Capability name.
	 * @return array<string>
	 */
	public function prevent_plugin_activation( $caps, $cap ) {
		if ( $this->prevent_activations && 'activate_plugins' === $cap ) {
			return [ 'do_not_allow' ];
		}

		return $caps;
	}
}

// Include an alias for legacy references to the former class namespace.
class_alias( WP_Plugin_Loader::class, 'Alley\WP\WP_Plugin_Loader\WP_Plugin_Loader' );
