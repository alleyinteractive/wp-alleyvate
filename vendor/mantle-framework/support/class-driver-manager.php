<?php
/**
 * Driver_Manager class file.
 *
 * @package Mantle
 */

namespace Mantle\Support;

use Closure;
use InvalidArgumentException;

/**
 * Driver Manager for managing multiple stores and pluggable drivers.
 */
abstract class Driver_Manager {
	/**
	 * Custom driver creators.
	 *
	 * @var array
	 */
	protected $custom_creators = [];

	/**
	 * Resolved cache stores.
	 *
	 * @var array
	 */
	protected $resolved;

	/**
	 * Retrieve the default store from the configuration.
	 */
	abstract protected function get_default_store(): string;

	/**
	 * Retrieve store configuration.
	 *
	 * @param string $name Store name.
	 */
	abstract protected function get_config( string $name ): array;

	/**
	 * Retrieve a store.
	 *
	 * @param string $name Store name, optional.
	 * @return mixed
	 */
	public function store( string $name = null ) {
		$name = $name ?: $this->get_default_store();

		if ( ! isset( $this->resolved[ $name ] ) ) {
			$this->resolved[ $name ] = $this->resolve( $name );
		}

		return $this->resolved[ $name ];
	}

	/**
	 * Resolve an instance of a store.
	 *
	 * @param string $name Store name.
	 * @return mixed
	 *
	 * @throws InvalidArgumentException Thrown on error resolving.
	 */
	protected function resolve( string $name ) {
		$config = $this->get_config( $name );
		$driver = Arr::pull( $config, 'driver' );

		if ( empty( $driver ) ) {
			throw new InvalidArgumentException( "Driver not specified for [$name]." );
		}

		return $this->resolve_driver( $driver, $config );
	}

	/**
	 * Resolve a store.
	 *
	 * @param string $driver Driver name.
	 * @param mixed  ...$args Arguments for the driver.
	 * @return mixed
	 *
	 * @throws InvalidArgumentException Thrown for unsupported driver.
	 */
	protected function resolve_driver( string $driver, ...$args ) {
		if ( isset( $this->custom_creators[ $driver ] ) ) {
			return $this->call_custom_creator( $driver, $args );
		}

		$method = 'create_' . str_replace( '-', '_', Str::snake( $driver ) ) . '_driver';

		if ( ! method_exists( $this, $method ) ) {
			throw new InvalidArgumentException( "Driver [$driver] not supported." );
		}

		return $this->$method( ...$args );
	}

	/**
	 * Call a custom driver.
	 *
	 * @param string $driver Driver name.
	 * @param array  $args Arguments for the creator.
	 * @return mixed
	 */
	protected function call_custom_creator( string $driver, array $args ) {
		return $this->custom_creators[ $driver ]( ...$args );
	}

	/**
	 * Extend the manager.
	 *
	 * @param string  $name Driver name.
	 * @param Closure $callback Closure to invoke.
	 * @return static
	 */
	public function extend( string $name, Closure $callback ) {
		$this->custom_creators[ $name ] = $callback;
		return $this;
	}

	/**
	 * Pass a static method call to the default store.
	 *
	 * @param string $method Method name.
	 * @param array  $args Arguments.
	 * @return mixed
	 */
	public function __call( string $method, array $args ) {
		return $this->store()->$method( ...$args );
	}
}
