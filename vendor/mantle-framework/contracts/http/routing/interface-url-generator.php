<?php
/**
 * Url_Generator class file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Http\Routing;

/**
 * URL Generator
 */
interface Url_Generator {
	/**
	 * Get the current URL for the request.
	 *
	 * @return string
	 */
	public function current();

	/**
	 * Get the URL for the previous request.
	 *
	 * @param string $fallback Fallback value, optional.
	 */
	public function previous( string $fallback = null ): string;

	/**
	 * Generate a URL to a specific path.
	 *
	 * @param string               $path URL Path.
	 * @param array<string, mixed> $extra_query Extra query parameters to be appended to the URL path.
	 * @param array                $extra_params Extra parameters to be appended to the URL path.
	 * @param bool                 $secure Flag if should be forced to be secure.
	 * @return string
	 */
	public function to( string $path, array $extra_query = [], array $extra_params = [], bool $secure = null );

	/**
	 * Generate a URL for a route.
	 *
	 * @param string $name Route name.
	 * @param array  $parameters Route parameters.
	 * @param bool   $absolute Flag if should be absolute.
	 *
	 * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException If route not found.
	 */
	public function route( string $name, array $parameters = [], bool $absolute = true ): string;
}
