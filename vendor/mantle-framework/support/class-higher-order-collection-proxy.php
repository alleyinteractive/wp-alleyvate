<?php
/**
 * Higher_Order_Collection_Proxy class file.
 *
 * @package Mantle
 */

namespace Mantle\Support;

/**
 * Higher Order Collection Proxy
 *
 * @mixin Enumerable
 */
class Higher_Order_Collection_Proxy {
	/**
	 * Create a new proxy instance.
	 *
	 * @param  Enumerable $collection The collection being operated on.
	 * @param  string     $method     The method being proxied.
	 * @return void
	 */
	public function __construct( protected Enumerable $collection, protected string $method ) {}

	/**
	 * Proxy accessing an attribute onto the collection items.
	 *
	 * @param  string $key
	 */
	public function __get( string $key ): mixed {
		return $this->collection->{ $this->method }(
			fn ( $value ) => is_array( $value ) ? $value[ $key ] : $value->{$key}
		);
	}

	/**
	 * Proxy a method call onto the collection items.
	 *
	 * @param  string $method
	 * @param  array  $parameters
	 */
	public function __call( string $method, array $parameters ): mixed {
		return $this->collection->{ $this->method }(
			fn ( $value ) => $value->{ $method }( ...$parameters )
		);
	}
}
