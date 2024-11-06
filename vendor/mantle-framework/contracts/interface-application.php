<?php
/**
 * Application Contract interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts;

use RuntimeException;
use Mantle\Contracts\Kernel as Kernel_Contract;
use Mantle\Support\Service_Provider;

/**
 * Application Contract
 */
interface Application extends Container {
	/**
	 * Getter for the base path.
	 *
	 * @param string $path Path to append.
	 */
	public function get_base_path( string $path = '' ): string;

	/**
	 * Set the base path for a application.
	 *
	 * @param string $path Path to set.
	 */
	public function set_base_path( string $path );

	/**
	 * Get the path to the application "app" directory.
	 *
	 * @param string $path Path to append, optional.
	 */
	public function get_app_path( string $path = '' ): string;

	/**
	 * Set the application directory.
	 *
	 * @param string $path Path to use.
	 * @return static
	 */
	public function set_app_path( string $path );

	/**
	 * Getter for the bootstrap path.
	 *
	 * @param string $path Path to append.
	 */
	public function get_bootstrap_path( string $path = '' ): string;

	/**
	 * Set the root URL of the application.
	 *
	 * @param string $url Root URL to set.
	 */
	public function set_root_url( string $url );

	/**
	 * Getter for the root URL.
	 * This would be the root URL to the WordPress installation.
	 *
	 * @param string $path Path to append.
	 */
	public function get_root_url( string $path = '' ): string;

	/**
	 * Get the cache folder root.
	 * Folder that stores all compiled server-side assets for the application.
	 */
	public function get_cache_path(): string;

	/**
	 * Get the cached Composer packages path.
	 *
	 * Used to store all auto-loaded packages that are Composer dependencies.
	 */
	public function get_cached_packages_path(): string;

	/**
	 * Get the cached model manifest path.
	 * Used to store all auto-registered models that are in the application.
	 */
	public function get_cached_models_path(): string;

	/**
	 * Get the path to the application configuration files.
	 */
	public function get_config_path(): string;

	/**
	 * Determine if the application has been bootstrapped before.
	 */
	public function has_been_bootstrapped(): bool;

	/**
	 * Get the Application's Environment
	 */
	public function environment(): string;

	/**
	 * Check if the Application's Environment matches a list.
	 *
	 * @param string|array ...$environments Environments to check.
	 */
	public function is_environment( ...$environments ): bool;

	/**
	 * Get the application namespace.
	 *
	 * @throws RuntimeException Thrown on error determining namespace.
	 */
	public function get_namespace(): string;

	/**
	 * Alias to get_namespace().
	 *
	 * @throws RuntimeException Thrown on error determining namespace.
	 */
	public function namespace(): string;

	/**
	 * Check if the application is running in the console.
	 */
	public function is_running_in_console(): bool;

	/**
	 * Check if the application is running in console isolation mode.
	 */
	public function is_running_in_console_isolation(): bool;

	/**
	 * Determine if the application has booted.
	 */
	public function is_booted(): bool;

	/**
	 * Boot the application's service providers.
	 */
	public function boot();

	/**
	 * Register a new boot listener.
	 *
	 * @param callable $callback Callback for the listener.
	 */
	public function booting( callable $callback ): static;

	/**
	 * Register a new "booted" listener.
	 *
	 * @param callable $callback Callback for the listener.
	 */
	public function booted( callable $callback ): static;

	/**
	 * Register a new terminating callback.
	 *
	 * @param callable $callback Callback for the listener.
	 */
	public function terminating( callable $callback ): static;

	/**
	 * Terminate the application.
	 */
	public function terminate(): void;

	/**
	 * Run the given array of bootstrap classes.
	 *
	 * Bootstrap classes should implement `Mantle\Contracts\Bootstrapable`.
	 *
	 * @param string[]        $bootstrappers Class names of packages to boot.
	 * @param Kernel_Contract $kernel Kernel instance.
	 */
	public function bootstrap_with( array $bootstrappers, Kernel_Contract $kernel );

	/**
	 * Get an instance of a service provider.
	 *
	 * @param string $name Provider class name.
	 */
	public function get_provider( string $name ): ?Service_Provider;

	/**
	 * Get all service providers.
	 *
	 * @return Service_Provider[]
	 */
	public function get_providers(): array;

	/**
	 * Determine if the application is cached.
	 */
	public function is_configuration_cached(): bool;

	/**
	 * Retrieve the cached configuration path.
	 */
	public function get_cached_config_path(): string;

	/**
	 * Determine if events are cached.
	 */
	public function is_events_cached(): bool;

	/**
	 * Retrieve the cached configuration path.
	 */
	public function get_cached_events_path(): string;

	/**
	 * Register a service provider.
	 *
	 * @param Service_Provider|class-string<Service_Provider> $provider Provider to register.
	 */
	public function register( Service_Provider|string $provider ): static;
}
