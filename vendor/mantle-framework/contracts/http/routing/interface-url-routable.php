<?php
/**
 * Url_Routable class file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Http\Routing;

/**
 * URL Routable Contract
 *
 * Provides an interface to route to a specific model.
 */
interface Url_Routable {

	/**
	 * Get the value of the model's route key.
	 *
	 * @return mixed
	 */
	public function get_route_key();

	/**
	 * Get the route key for the model.
	 */
	public function get_route_key_name(): string;

	/**
	 * Get route for the model.
	 */
	public static function get_route(): ?string;

	/**
	 * Get archive route for the model.
	 */
	public static function get_archive_route(): ?string;

	/**
	 * Retrieve the model for a bound value.
	 *
	 * @param  mixed       $value
	 * @param  string|null $field
	 * @return static|null
	 */
	public function resolve_route_binding( $value, $field = null );
}
