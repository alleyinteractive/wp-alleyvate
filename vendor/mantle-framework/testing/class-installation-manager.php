<?php
/**
 * Installation_Manager class file
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Support\Traits\Conditionable;
use Mantle\Support\Traits\Singleton;

/**
 * Installation Manager
 */
class Installation_Manager {
	use Conditionable;
	use Concerns\PHPUnit_Upgrade_Warning;
	use Concerns\Rsync_Installation;
	use Singleton;

	/**
	 * Callbacks for before installation.
	 *
	 * @var callable[]
	 */
	protected array $before_install_callbacks = [];

	/**
	 * Callbacks for after installation.
	 *
	 * @var callable[]
	 */
	protected array $after_install_callbacks = [];

	/**
	 * Callback for after WordPress is loaded.
	 *
	 * @var callable[]
	 */
	protected array $after_loaded_callbacks = [];

	/**
	 * Constructor.
	 *
	 * Ensure that any environment variables also call the subsequent methods to
	 * configure the installation.
	 */
	public function __construct() {
		$this->with_default_exclusions();

		if ( Utils::env_bool( 'MANTLE_INSTALL_VIP_MU_PLUGINS', false ) ) {
			$this->with_vip_mu_plugins();
		}

		if ( Utils::env_bool( 'MANTLE_INSTALL_OBJECT_CACHE', false ) ) {
			$this->with_object_cache();
		} elseif ( $object_cache = Utils::env( 'MANTLE_INSTALL_OBJECT_CACHE', false ) ) {
			$this->with_object_cache( $object_cache );
		}

		if ( Utils::env_bool( 'MANTLE_USE_SQLITE', false ) ) {
			$this->with_sqlite();
		}
	}

	/**
	 * Define a callback to be invoked before installation.
	 *
	 * @param callable $callback Callback to invoke before installation.
	 * @return static
	 */
	public function before( ?callable $callback ) {
		if ( is_callable( $callback ) ) {
			$this->before_install_callbacks[] = $callback;
		}

		return $this;
	}

	/**
	 * Define a callback to be invoked after installation.
	 *
	 * @param callable|null $callback Callback to invoke after installation.
	 * @param bool          $append Whether to append the callback to the list or prepend it.
	 * @return static
	 */
	public function after( ?callable $callback, bool $append = true ) {
		if ( is_callable( $callback ) ) {
			$append
				? $this->after_install_callbacks[] = $callback
				: array_unshift( $this->after_install_callbacks, $callback );
		}

		return $this;
	}

	/**
	 * Define a callback for a specific WordPress hook.
	 *
	 * @param string   $hook Hook name.
	 * @param callable $callback Callback to invoke.
	 * @param int      $priority Priority.
	 * @param int      $accepted_args Number of accepted arguments.
	 * @return static
	 */
	public function on( string $hook, ?callable $callback, int $priority = 10, int $accepted_args = 1 ) {
		if ( is_callable( $callback ) ) {
			tests_add_filter( $hook, $callback, $priority, $accepted_args );
		}

		return $this;
	}

	/**
	 * Define a callback to be invoked using the 'muplugins_loaded' hook.
	 *
	 * @param callable $callback Callback to invoke on 'muplugins_loaded'.
	 * @return static
	 */
	public function loaded( ?callable $callback ) {
		return $this->on( 'muplugins_loaded', $callback );
	}

	/**
	 * Define a callback to be invoked on 'init'.
	 *
	 * @param callable $callback Callback to invoke on 'init'.
	 * @return static
	 */
	public function init( ?callable $callback ) {
		return $this->loaded(
			fn () => $this->on( 'init', $callback )
		);
	}

	/**
	 * Define the active theme to be set after the installation is loaded.
	 *
	 * @param string $theme Theme name.
	 * @return static
	 */
	public function theme( string $theme ) {
		return $this->loaded( fn () => switch_theme( $theme ) );
	}

	/**
	 * Alias for `theme()`.
	 *
	 * @param string $theme Theme name.
	 * @return static
	 */
	public function with_theme( string $theme ) {
		return $this->theme( $theme );
	}

	/**
	 * Define the active plugins to be set after the installation is loaded.
	 *
	 * To install a remote plugin to the installation during the rsync process,
	 * use the `install_plugin()` method.
	 *
	 * @see \Mantle\Testing\Concerns\Rsync_Installation::install_plugin()
	 *
	 * @param array<int, string> $plugins Plugin files to activate in WordPress.
	 * @return static
	 */
	public function plugins( array $plugins ) {
		return $this->loaded( fn () => update_option( 'active_plugins', $plugins ) );
	}

	/**
	 * Alias for `plugins()`.
	 *
	 * @param array<int, string> $plugins Plugin files to activate in WordPress.
	 * @return static
	 */
	public function with_plugins( array $plugins ) {
		return $this->plugins( $plugins );
	}

	/**
	 * Alias for `plugins()`.
	 *
	 * @param array<int, string> $plugins Plugin files to activate in WordPress.
	 */
	public function with_active_plugins( array $plugins ): static {
		return $this->plugins( $plugins );
	}

	/**
	 * Install the Mantle Testing Framework.
	 *
	 * @return static
	 */
	public function install() {
		$this->warn_if_phpunit_10_or_higher();

		require_once __DIR__ . '/core-polyfill.php';

		if ( Utils::is_debug_mode() ) {
			Utils::info( '🚨 Debug mode is enabled.' );
		}

		if ( $this->rsync_to ) {
			$this->perform_rsync_testsuite();
			return $this;
		}

		foreach ( $this->before_install_callbacks as $before_install_callback ) {
			$before_install_callback();
		}

		try {
			require_once __DIR__ . '/wordpress-bootstrap.php';
		} catch ( \Throwable $throwable ) {
			Utils::error( '🚨 Failed to load the WordPress installation. Exception thrown:' );
			Utils::code( $throwable->getMessage() );
			exit( 1 );
		}

		foreach ( $this->after_install_callbacks as $after_install_callback ) {
			$after_install_callback();
		}

		return $this;
	}
}
