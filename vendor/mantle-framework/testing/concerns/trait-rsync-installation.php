<?php
/**
 * Rsync_Installation trait file
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
 * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Support\Traits\Conditionable;
use Mantle\Testing\Utils;

use function Mantle\Support\Helpers\collect;

/**
 * Trait to manage rsync-ing the codebase to live within a WordPress
 * installation.
 *
 * WordPress plugins/themes commonly need live within within a valid WordPress
 * installation. This can allow for end-to-end testing of a WordPress
 * plugin/theme from code that is written in a standalone repository. For
 * example, a repository can contain only a theme. Mantle will internally rsync
 * the theme to a WordPress installation without needing to run a bash script.
 *
 * After the rsync is complete, PHPUnit will be rerun from the new location.
 *
 * @mixin \Mantle\Testing\Installation_Manager
 */
trait Rsync_Installation {
	use Conditionable;

	/**
	 * Storage location to rsync the codebase to.
	 */
	protected ?string $rsync_to = null;

	/**
	 * Storage location to rsync the codebase from.
	 */
	protected ?string $rsync_from = null;

	/**
	 * Subdirectory from the parent folder being rsync-ed to the previous working
	 * directory.
	 */
	protected ?string $rsync_subdir = '';

	/**
	 * Plugin slugs or URLs to ZIP files to install after rsyncing the codebase.
	 *
	 * @var array<int, array{0: string, 1: string|null}>
	 */
	protected array $plugins = [];

	/**
	 * Exclusions to be used when rsyncing the codebase.
	 *
	 * @var string[]
	 */
	protected array $rsync_exclusions = [];

	/**
	 * Add the default set of exclusions to the list of exclusions to be used when rsyncing the codebase.
	 */
	public function with_default_exclusions(): static {
		return $this->exclusions(
			[
				'.buddy',
				'.git',
				'.github',
				'.npm',
				'.phpcs',
				'.turbo',
				'.phpunit.result.cache',
				'node_modules',
				'phpstan.neon',
			]
		);
	}

	/**
	 * Rsync the code base to be located under a valid WordPress installation.
	 *
	 * By default, the codebase will be rsynced to the `wp-content` directory. The
	 * `to` path is assumed to be relative to the `wp-content` folder.
	 *
	 * @param string $to Location to rsync to within `wp-content`.
	 * @param string $from Location to rsync from.
	 */
	public function rsync( string $to = null, string $from = null ): static {
		$this->rsync_to   = $to ?: '/';
		$this->rsync_from = $from ?: getcwd() . '/';

		return $this;
	}

	/**
	 * Rsync the code base to be located underneath a WordPress installation if it
	 * isn't already.
	 *
	 * @param string $to Location to rsync to.
	 * @param string $from Location to rsync from.
	 */
	public function maybe_rsync( string $to = null, string $from = null ): static {
		// Check if we are under an existing WordPress installation.
		if ( $this->is_within_wordpress_install() ) {
			return $this;
		}

		return $this->rsync( $to, $from );
	}

	/**
	 * Maybe rsync the codebase to the wp-content within WordPress.
	 *
	 * Will attempt to locate the wp-content directory relative to the current
	 * directory. As a fallback, it will assume it is being called from either
	 * /wp-content/plugin/:plugin/tests OR /wp-content/themes/:theme/tests. Will
	 * rsync the codebase from the wp-content level to the root of the WordPress
	 * installation. Also will attempt to locate the wp-content directory relative
	 * to the current directory.
	 *
	 * This isn't a perfect function and can sometimes fail to locate the proper
	 * `wp-content` directory. If it does fail to work, manually call
	 * `maybe_rsync()` yourself with the proper paths.
	 */
	public function maybe_rsync_wp_content(): static {
		// Attempt to locate wp-content relative to the current directory.
		if ( false !== strpos( __DIR__, '/wp-content/' ) ) {
			return $this->maybe_rsync( '/', preg_replace( '/\/wp-content\/.*$/', '/wp-content', __DIR__ ) );
		} elseif ( preg_match( '/\/(?:client-mu-plugins|mu-plugins|plugins|themes)\/.*/', __DIR__ ) ) {
			/**
			 * Attempt to locate the wp-content directory relative to the current
			 * directory by finding the WordPress-parent folder after wp-content. Used
			 * when the directory structure doesn't contain wp-content but contains a
			 * subfolder that we can use to locate the WordPress installation such as
			 * plugins, themes, etc. This is common for wp-content-rooted projects
			 * that have the root of their directory structure as the wp-content
			 * folder.
			 */
			return $this->maybe_rsync( '/', preg_replace( '/\/(?:client-mu-plugins|mu-plugins|plugins|themes)\/.*/', '', __DIR__ ) );
		}

		return $this->maybe_rsync( '/', dirname( getcwd(), 3 ) );
	}

	/**
	 * Attempt to install VIP's built mu-plugins into the codebase.
	 *
	 * Will only be applied if the codebase is not already within a WordPress and
	 * is being rsync-ed to one.
	 *
	 * @param bool $install Install VIP's built mu-plugins into the codebase.
	 */
	public function with_vip_mu_plugins( bool $install = true ): static {
		if ( $this->is_within_wordpress_install() ) {
			return $this;
		}

		$this->add_exclusion( 'mu-plugins' );

		putenv( 'MANTLE_INSTALL_VIP_MU_PLUGINS=' . ( $install ? '1' : '0' ) );

		return $this;
	}

	/**
	 * Attempt to install the object cache drop-in into the codebase.
	 *
	 * Will only be applied if the codebase is not already within a WordPress and
	 * is being rsync-ed to one.
	 *
	 * @param bool|string $install The object cache provider to install (redis/memcached)
	 *                             or true to install the default Memcached object cache (legacy).
	 */
	public function with_object_cache( bool|string $install = true ): static {
		if ( $this->is_within_wordpress_install() ) {
			return $this;
		}

		// Allow object cache to be disabled.
		if ( ! $install ) {
			putenv( 'MANTLE_INSTALL_OBJECT_CACHE=' );

			return $this;
		} elseif ( true === $install ) {
			$install = 'memcached';
		}

		if ( ! in_array( $install, [ 'redis', 'memcached' ], true ) ) {
			Utils::error( 'Invalid object cache provider. Must be either "redis" or "memcached". Skipping...' );

			return $this;
		}

		if ( 'memcached' === $install ) {
			// Check if Memcached is installed before proceeding.
			if ( ! class_exists( \Memcached::class ) && ! Utils::env( 'MANTLE_REQUIRE_OBJECT_CACHE', false ) ) {
				Utils::error( 'Memcached is not installed. Cannot install object cache. Skipping...' );

				return $this;
			}
		}

		$this->add_exclusion( 'object-cache.php' );

		putenv( 'MANTLE_INSTALL_OBJECT_CACHE=' . $install );

		return $this;
	}

	/**
	 * Install SQLite db.php drop-in into the codebase.
	 *
	 * This will only be applied if the codebase is being rsync-ed to a WordPress
	 * installation.
	 *
	 * @param bool $install Install the SQLite db.php drop-in into the codebase.
	 */
	public function with_sqlite( bool $install = true ): static {
		if ( $this->is_within_wordpress_install() ) {
			return $this;
		}

		putenv( 'MANTLE_USE_SQLITE=' . ( $install ? '1' : '0' ) );
		putenv( 'WP_SKIP_DB_CREATE=1' );

		$this->exclusions(
			[
				'db.php',
				'sqlite-database-integration',
			]
		);

		return $this;
	}

	/**
	 * Install a specific plugin into the rsync-ed codebase.
	 *
	 * Used to install a plugin from WordPress.org or a ZIP file to the codebase
	 * after rsyncing.
	 *
	 * @param string $plugin Plugin slug to install. Will be installed at /wp-content/plugins/{plugin}.
	 * @param string $version_or_url Plugin version to install OR a URL to a ZIP file to install.
	 */
	public function install_plugin( string $plugin, string $version_or_url = 'latest' ): static {
		// Ensure that the plugin slug is not a URL.
		if ( false !== strpos( $plugin, '://' ) ) {
			Utils::error(
				'Plugin slug cannot be a URL. Please provide a plugin slug and specify the URL or the version in the second argument.',
				'Install Rsync'
			);

			exit( 1 );
		}

		$this->plugins[] = [ $plugin, $version_or_url ];

		return $this;
	}

	/**
	 * Maybe rsync the codebase as a plugin within WordPress.
	 *
	 * By default, the from path will be rsynced to `wp-content/plugins/{directory_name}`.
	 *
	 * @param string $name Name of the plugin folder, optional.
	 * @param string $from Location to rsync from.
	 */
	public function maybe_rsync_plugin( string $name = null, string $from = null ): static {
		if ( ! $name ) {
			$name = basename( getcwd() );
		}

		return $this->maybe_rsync( "plugins/{$name}", $from );
	}

	/**
	 * Maybe rsync the codebase as a theme within WordPress.
	 *
	 * By default, the from path will be rsynced to `wp-content/themes/{directory_name}`.
	 *
	 * @param string $name Name of the theme folder, optional.
	 * @param string $from Location to rsync from.
	 */
	public function maybe_rsync_theme( string $name = null, string $from = null ): static {
		if ( ! $name ) {
			$name = basename( getcwd() );
		}

		return $this->maybe_rsync( "themes/{$name}", $from );
	}

	/**
	 * Specify the exclusions to be used when rsyncing the codebase.
	 *
	 * @param string[] $exclusions Exclusions to be used when rsyncing the codebase.
	 * @param bool     $merge Whether to merge the exclusions with the default exclusions.
	 */
	public function exclusions( array $exclusions, bool $merge = true ): static {
		$this->rsync_exclusions = collect( $merge ? $this->rsync_exclusions : [] )
			->merge( $exclusions )
			->unique()
			->values()
			->all();

		return $this;
	}

	/**
	 * Add an exclusion to the list of exclusions to be used when rsyncing the codebase.
	 *
	 * @param string $exclusion Exclusion to add to the list of exclusions.
	 */
	public function add_exclusion( string $exclusion ): static {
		return $this->exclusions( [ $exclusion ], true );
	}

	/**
	 * Remove an exclusion from the list of exclusions to be used when rsyncing the codebase.
	 *
	 * @param string $exclusion Exclusion to remove from the list of exclusions.
	 */
	public function remove_exclusion( string $exclusion ): static {
		$this->rsync_exclusions = collect( $this->rsync_exclusions )
			->filter( fn ( $item ) => $item !== $exclusion )
			->unique()
			->values()
			->all();

		return $this;
	}

	/**
	 * Retrieve the default installation path to rsync to.
	 */
	protected function get_installation_path(): string {
		return getenv( 'WP_CORE_DIR' ) ?: sys_get_temp_dir() . '/wordpress';
	}

	/**
	 * Check if the current installation is underneath an existing WordPress
	 * installation.
	 */
	protected function is_within_wordpress_install(): bool {
		return false !== strpos( __DIR__, '/wp-content/' );
	}

	/**
	 * Rsync the codebase before installation.
	 *
	 * This allows the plugin/theme project to properly situate itself within a
	 * WordPress installation without needing to rsync it manually.
	 */
	protected function perform_rsync_testsuite() {
		require_once __DIR__ . '/../class-utils.php';

		$base_install_path = $this->get_installation_path();

		// Normalize the rsync paths. Ensure that both have a trailing slash to be
		// inclusive of the directory's contents and not just the directory itself.
		$this->rsync_from = rtrim( $this->rsync_from, '/' ) . '/';
		$this->rsync_to   = rtrim( "$base_install_path/wp-content/{$this->rsync_to}", '/' ) . '/';

		// Store the subdirectory of the current working directory relative to the
		// from rsync path.
		$this->rsync_subdir = str_replace( $this->rsync_from, '', rtrim( getcwd(), '/' ) . '/' );

		// Define the constants relative to where the codebase is being rsynced to.
		defined( 'WP_TESTS_INSTALL_PATH' ) || define( 'WP_TESTS_INSTALL_PATH', $base_install_path );
		defined( 'WP_TESTS_CONFIG_FILE_PATH' ) || define( 'WP_TESTS_CONFIG_FILE_PATH', "{$base_install_path}/wp-tests-config.php" );
		defined( 'ABSPATH' ) || define( 'ABSPATH', ensure_trailingslash( $base_install_path ) );

		// Install WordPress at the base installation if it doesn't exist yet.
		if ( ! is_dir( $base_install_path ) || ! is_file( "{$base_install_path}/wp-load.php" ) ) {
			Utils::info(
				"Installing WordPress at <em>{$base_install_path}</em> ...",
				'Install Rsync'
			);

			// Create the installation directory.
			if ( ! is_dir( $base_install_path ) && ! mkdir( $base_install_path, 0777, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
				Utils::error(
					"Unable to create the WordPress installation directory at <em>{$base_install_path}</em>",
					'Install Rsync'
				);

				exit( 1 );
			}

			Utils::install_wordpress( $base_install_path );
			Utils::success(
				"WordPress installed at <em>{$base_install_path}</em>",
				'Install Rsync'
			);
		} else {
			Utils::info(
				"WordPress already installed at <em>{$base_install_path}</em>",
				'Install Rsync'
			);
		}

		Utils::info(
			"Rsyncing <em>{$this->rsync_from}</em> to <em>{$this->rsync_to}</em>...",
			'Install Rsync'
		);

		if ( ! is_dir( $this->rsync_to ) && ! mkdir( $this->rsync_to, 0777, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			Utils::error(
				"Unable to create destination directory [{$this->rsync_to}]."
			);

			exit( 1 );
		}

		$retval = -1;

		// Rsync the from folder to the destination.
		$output = Utils::command(
			[
				'rsync -aWq --no-compress',
				collect( $this->rsync_exclusions )->map( fn ( $exclusion ) => "--exclude '{$exclusion}'" )->implode( ' ' ),
				'--delete',
				"{$this->rsync_from} {$this->rsync_to}",
			],
			$retval
		);

		if ( 0 !== $retval ) {
			Utils::error( '🚨 Error rsyncing! Output from command:', 'Install Rsync' );
			Utils::code( $output );
			exit( 1 );
		}

		if ( ! chdir( $this->rsync_to . $this->rsync_subdir ) ) {
			// Fallback to just the rsync_to directory without the subdirectory.
			if ( ! chdir( $this->rsync_to ) ) {
				Utils::error(
					"Unable to change directory to <em>{$this->rsync_to}</em>",
					'Install Rsync'
				);
			}

			exit( 1 );
		}

		$cwd = getcwd();

		if ( ! empty( $this->plugins ) ) {
			$this->install_plugins( $base_install_path );
		}

		Utils::success(
			"Finished rsyncing to <em>{$this->rsync_to}</em> and working directory is now <em>{$cwd}</em>",
			'Install Rsync'
		);

		$command = $this->get_phpunit_command();

		// Proxy to the phpunit instance within the new rsynced WordPress installation.
		Utils::info(
			"Running <em>{$command}</em> in <em>{$cwd}</em>",
			'Install Rsync'
		);

		system( $command, $result_code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_system

		exit( (int) $result_code );
	}

	/**
	 * Install the plugins after rsyncing the codebase.
	 *
	 * @param string $dir Directory to the WordPress installation.
	 */
	protected function install_plugins( string $dir ): void {
		foreach ( $this->plugins as $item ) {
			[ $plugin, $version_or_url ] = $item;

			if ( empty( $version_or_url ) ) {
				$version_or_url = 'latest';
			}

			Utils::info(
				"Installing plugin <em>{$plugin}</em> ({$version_or_url})...",
				'Install Rsync'
			);

			Utils::install_plugin( $dir, $plugin, $version_or_url );
		}
	}

	/**
	 * Generate the command that will be run inside the rsync-ed WordPress
	 * installation to fire off PHPUnit.
	 */
	protected function get_phpunit_command(): string {
		$args = (array) ( $_SERVER['argv'] ?? [] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! empty( getenv( 'WP_PHPUNIT_PATH' ) ) ) {
			$executable = getenv( 'WP_PHPUNIT_PATH' );
		} elseif ( ! empty( $args[0] ) && false !== strpos( (string) $args[0], 'phpunit' ) ) {
			// Use the first argument and translate it to the rsync-ed path.
			$executable = $this->translate_location( $args[0] );

			// Attempt to fallback to the phpunit binary reference in PHP_SELF. This
			// would be the one used to invoke the current script. With that, we can
			// translate it to the new location in the rsync-ed WordPress
			// installation.
			if (
				! empty( $_SERVER['PHP_SELF'] )
				&& ! is_file( $executable )
				&& ! is_executable( $executable )
				&& ! str_starts_with( 'composer ', (string) $executable )
			) {
				$executable = $this->translate_location( $_SERVER['PHP_SELF'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
		}

		// Default to a local phpunit in the vendor directory.
		if ( empty( $executable ) ) {
			$executable = 'vendor/bin/phpunit';
		}

		// Remove the first argument, which is the path to the phpunit binary. The
		// rest will be forwarded to the command.
		array_shift( $args );

		return $executable . ' ' . implode( ' ', array_map( 'escapeshellarg', $args ) );
	}

	/**
	 * Translate a path from the rsync-ed WordPress installation to the original
	 * location.
	 *
	 * @param string $path Path to translate.
	 */
	protected function translate_location( string $path ): string {
		return str_replace( $this->rsync_from, $this->rsync_to, $path );
	}
}
