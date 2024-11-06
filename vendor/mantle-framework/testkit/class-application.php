<?php
/**
 * Application class file
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn
 *
 * @package Mantle
 */

namespace Mantle\Testkit;

use Faker\Generator;
use Faker\Factory;
use Mantle\Container\Container;
use Mantle\Contracts\Application as Application_Contract;
use Mantle\Contracts\Container as Container_Contract;
use Mantle\Contracts\Kernel as Kernel_Contract;
use Mantle\Events\Dispatcher;
use Mantle\Faker\Faker_Provider;
use Mantle\Support\Environment;
use Mantle\Support\Service_Provider;
use RuntimeException;

/**
 * Testkit Application
 *
 * For use of the Mantle testing framework entirely independent of the Mantle framework.
 */
class Application extends Container implements Application_Contract {
	/**
	 * Base path of the application.
	 *
	 * @var string
	 */
	protected $base_path;

	/**
	 * Application path of the application.
	 *
	 * @var string
	 */
	protected $app_path;

	/**
	 * Bootstrap path of the application.
	 *
	 * @var string
	 */
	protected $bootstrap_path;

	/**
	 * Storage path of the application.
	 *
	 * @var string
	 */
	protected $storage_path;

	/**
	 * Root URL of the application.
	 *
	 * @var string
	 */
	protected $root_url;

	/**
	 * Indicates if the application has been bootstrapped before.
	 *
	 * @var bool
	 */
	protected $has_been_bootstrapped = false;

	/**
	 * Indicates if the application has "booted".
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * Environment file name.
	 *
	 * @var string
	 */
	protected $environment_file = '.env';

	/**
	 * The custom environment path defined by the developer.
	 *
	 * @var string
	 */
	protected $environment_path;

	/**
	 * Storage of the overridden environment name.
	 *
	 * @var string
	 */
	protected $environment;

	/**
	 * Constructor.
	 *
	 * @param string $base_path Base path to set.
	 * @param string $root_url Root URL of the application.
	 */
	public function __construct( string $base_path = '', string $root_url = null ) {
		static::$instance = $this;

		if ( empty( $base_path ) && defined( 'MANTLE_BASE_DIR' ) ) {
			$base_path = \MANTLE_BASE_DIR;
		}

		if ( ! $root_url ) {
			$root_url = \home_url();
		}

		$this->set_base_path( $base_path );
		$this->set_root_url( $root_url );
		$this->register_base_bindings();
		$this->register_base_service_providers();
	}

	/**
	 * Set the base path of the application.
	 *
	 * @param string $path Path to set.
	 * @return static
	 */
	public function set_base_path( string $path ) {
		$this->base_path = $path;

		$this->instance( 'path', $this->get_base_path() );
		$this->instance( 'path.bootstrap', $this->get_bootstrap_path() );
		$this->instance( 'path.storage', $this->get_storage_path() );

		return $this;
	}

	/**
	 * Getter for the base path.
	 *
	 * @param string $path Path to append.
	 */
	public function get_base_path( string $path = '' ): string {
		return $this->base_path . ( $path ? DIRECTORY_SEPARATOR . $path : '' );
	}

	/**
	 * Get the path to the application "app" directory.
	 *
	 * @param string $path Path to append, optional.
	 */
	public function get_app_path( string $path = '' ): string {
		$app_path = $this->app_path ?: $this->get_base_path( 'app' );

		return $app_path . ( $path ? DIRECTORY_SEPARATOR . $path : $path );
	}

	/**
	 * Set the application directory.
	 *
	 * @param string $path Path to use.
	 * @return static
	 */
	public function set_app_path( string $path ) {
		$this->app_path = $path;

		$this->instance( 'path', $path );

		return $this;
	}

	/**
	 * Getter for the bootstrap path.
	 *
	 * @param string $path Path to append.
	 */
	public function get_bootstrap_path( string $path = '' ): string {
		return ( $this->bootstrap_path ?: $this->base_path . DIRECTORY_SEPARATOR . 'bootstrap' ) . $path;
	}

	/**
	 * Getter for the storage path.
	 *
	 * @param string $path Path to append.
	 */
	public function get_storage_path( string $path = '' ): string {
		return ( $this->storage_path ?: $this->base_path . DIRECTORY_SEPARATOR . 'storage' ) . $path;
	}

	/**
	 * Set the root URL of the application.
	 *
	 * @param string $url Root URL to set.
	 */
	public function set_root_url( string $url ): void {
		$this->root_url = $url;
	}

	/**
	 * Getter for the root URL.
	 *
	 * @param string $path Path to append.
	 */
	public function get_root_url( string $path = '' ): string {
		return $this->root_url . ( $path ? DIRECTORY_SEPARATOR . $path : '' );
	}

	/**
	 * Get the cache folder root
	 * Folder that stores all compiled server-side assets for the application.
	 */
	public function get_cache_path(): string {
		return $this->get_bootstrap_path( '/cache' );
	}

	/**
	 * Get the cached Composer packages path.
	 *
	 * Used to store all auto-loaded packages that are Composer dependencies.
	 */
	public function get_cached_packages_path(): string {
		return $this->get_cache_path() . '/packages.php';
	}

	/**
	 * Get the cached model manifest path.
	 * Used to store all auto-registered models that are in the application.
	 */
	public function get_cached_models_path(): string {
		return '';
	}

	/**
	 * Determine if the application is cached.
	 */
	public function is_configuration_cached(): bool {
		return false;
	}

	/**
	 * Retrieve the cached configuration path.
	 */
	public function get_cached_config_path(): string {
		return '';
	}

	/**
	 * Determine if events are cached.
	 */
	public function is_events_cached(): bool {
		return false;
	}

	/**
	 * Retrieve the cached configuration path.
	 */
	public function get_cached_events_path(): string {
		return '';
	}

	/**
	 * Get the path to the application configuration files.
	 */
	public function get_config_path(): string {
		return '';
	}

	/**
	 * Determine if the application has been bootstrapped before.
	 */
	public function has_been_bootstrapped(): bool {
		return (bool) $this->has_been_bootstrapped;
	}

	/**
	 * Register the basic bindings into the container.
	 *
	 * @return void
	 */
	protected function register_base_bindings() {
		static::set_instance( $this );

		$this->instance( 'app', $this );
		$this->instance( Container::class, $this );
		$this->instance( Container_Contract::class, $this );
		$this->instance( static::class, $this );
	}

	/**
	 * Register the base service providers.
	 */
	protected function register_base_service_providers() {
		$this->singleton( 'events', fn ( $app ) => new Dispatcher( $app ) );

		$this->singleton(
			Generator::class,
			function () {
				$factory = Factory::create();

				$factory->unique( true );

				$factory->addProvider( new Faker_Provider( $factory ) );

				return $factory;
			},
		);
	}

	/**
	 * Run the given array of bootstrap classes.
	 *
	 * @throws RuntimeException Thrown on use.
	 *
	 * @param string[]        $bootstrappers Class names of packages to boot.
	 * @param Kernel_Contract $kernel Kernel instance.
	 */
	public function bootstrap_with( array $bootstrappers, Kernel_Contract $kernel ): never {
		throw new RuntimeException( 'Not supported with Testkit' );
	}

	/**
	 * Get an instance of a service provider.
	 *
	 * @throws RuntimeException Thrown on use.
	 *
	 * @param string $name Provider class name.
	 */
	public function get_provider( string $name ): ?Service_Provider {
		throw new RuntimeException( 'Not supported with Testkit' );
	}

	/**
	 * Get all service providers.
	 */
	public function get_providers(): array {
		return [];
	}

	/**
	 * Register a Service Provider
	 *
	 * @throws RuntimeException Thrown on use.
	 *
	 * @param Service_Provider|string $provider Service provider to register.
	 */
	public function register( Service_Provider|string $provider ): static {
		throw new RuntimeException( 'Not supported with Testkit' );
	}

	/**
	 * Determine if the application has booted.
	 */
	public function is_booted(): bool {
		return $this->booted;
	}

	/**
	 * Boot the application's service providers.
	 *
	 * @return static
	 */
	public function boot() {
		$this->booted = true;

		return $this;
	}

	/**
	 * Set and retrieve the environment file name.
	 *
	 * @param string $file File name to set.
	 */
	public function environment_file( string $file = null ): string {
		if ( $file ) {
			$this->environment_file = $file;
		}

		return $this->environment_file ?: '.env';
	}

	/**
	 * Set and retrieve the environment path to use.
	 *
	 * @param string $path Path to set, optional.
	 * @return string
	 */
	public function environment_path( string $path = null ): ?string {
		if ( $path ) {
			$this->environment_path = $path;
		}

		return $this->environment_path;
	}

	/**
	 * Get the Application's Environment
	 */
	public function environment(): string {
		if ( ! empty( $this->environment ) ) {
			return $this->environment;
		}

		return Environment::get( 'ENV', wp_get_environment_type() );
	}

	/**
	 * Check if the Application's Environment matches a list.
	 *
	 * @param string|array ...$environments Environments to check.
	 */
	public function is_environment( ...$environments ): bool {
		return in_array( $this->environment(), $environments, true );
	}

	/**
	 * Get the application namespace.
	 *
	 * @throws RuntimeException Thrown on error determining namespace.
	 */
	public function get_namespace(): string {
		return Environment::get( 'APP_NAMESPACE', 'App' );
	}

	/**
	 * Alias to get_namespace().
	 *
	 * @throws RuntimeException Thrown on error determining namespace.
	 */
	public function namespace(): string {
		return $this->get_namespace();
	}

	/**
	 * Check if the application is running in the console (wp-cli).
	 */
	public function is_running_in_console(): bool {
		return false;
	}

	/**
	 * Check if the application is running in console isolation mode.
	 */
	public function is_running_in_console_isolation(): bool {
		return false;
	}

	/**
	 * Set the environment for the application.
	 *
	 * @param string $environment Environment to set.
	 * @return static
	 */
	public function set_environment( string $environment ) {
		$this->environment = $environment;
		return $this;
	}

	/**
	 * Register a new boot listener.
	 *
	 * @throws RuntimeException Thrown on use.
	 *
	 * @param callable $callback Callback for the listener.
	 */
	public function booting( callable $callback ): static {
		throw new RuntimeException( 'Not supported with Testkit' );
	}

	/**
	 * Register a new "booted" listener.
	 *
	 * @throws RuntimeException Thrown on use.
	 *
	 * @param callable $callback Callback for the listener.
	 */
	public function booted( callable $callback ): static {
		throw new RuntimeException( 'Not supported with Testkit' );
	}

	/**
	 * Register a new terminating callback.
	 *
	 * @throws RuntimeException Thrown on use.
	 *
	 * @param callable $callback Callback for the listener.
	 */
	public function terminating( callable $callback ): static {
		throw new RuntimeException( 'Not supported with Testkit' );
	}

	/**
	 * Terminate the application.
	 *
	 * @throws RuntimeException Thrown on use.
	 */
	public function terminate(): void {
		throw new RuntimeException( 'Not supported with Testkit' );
	}
}
