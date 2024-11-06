<?php
/**
 * Request class file.
 *
 * @package Mantle
 *
 * @phpcs:disable Squiz.Commenting.FunctionComment.MissingParamComment
 */

namespace Mantle\Http;

use ArrayAccess;
use Mantle\Contracts\Support\Arrayable;
use Mantle\Http\Routing\Route;
use Mantle\Support\Arr;
use Mantle\Support\Str;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

use function Mantle\Support\Helpers\data_get;

/**
 * Request Object
 */
class Request extends SymfonyRequest implements ArrayAccess, Arrayable {
	use Interacts_With_Input;
	use Concerns\Interacts_With_Content_Types;

	/**
	 * Route parameters.
	 */
	protected ?ParameterBag $route_parameters = null;

	/**
	 * The decoded JSON content for the request.
	 */
	protected ?ParameterBag $json = null;

	/**
	 * Route matched.
	 *
	 * @var Route|null
	 */
	protected $route;

	/**
	 * All of the converted files for the request.
	 *
	 * @var array|null
	 */
	protected $converted_files;

	/**
	 * Create a request object.
	 *
	 * @return static
	 */
	public static function capture() {
		return static::createFromGlobals();
	}

	/**
	 * Set the path info for the request.
	 *
	 * @param string $path_info Path info.
	 * @return static
	 */
	public function setPathInfo( string $path_info ) {
		$this->pathInfo = $path_info; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		return $this;
	}

	/**
	 * Return the Request instance.
	 *
	 * @return static
	 */
	public function instance() {
		return $this;
	}

	/**
	 * Get the request method.
	 *
	 * @return string
	 */
	public function method() {
		return $this->getMethod();
	}

	/**
	 * Get the root URL for the application.
	 *
	 * @return string
	 */
	public function root() {
		return rtrim( $this->getSchemeAndHttpHost() . $this->getBaseUrl(), '/' );
	}

	/**
	 * Get the URL (no query string) for the request.
	 *
	 * @return string
	 */
	public function url() {
		return rtrim( (string) preg_replace( '/\?.*/', '', $this->getUri() ), '/' );
	}

	/**
	 * Get the full URL for the request.
	 *
	 * @return string
	 */
	public function full_url() {
		$query = $this->getQueryString();

		$question = $this->getBaseUrl() . $this->getPathInfo() === '/' ? '/?' : '?';

		return $query ? $this->url() . $question . $query : $this->url();
	}

	/**
	 * Get the full URL for the request with the added query string parameters.
	 *
	 * @param  array $query
	 * @return string
	 */
	public function full_url_with_query( array $query ) {
		$question = $this->getBaseUrl() . $this->getPathInfo() === '/' ? '/?' : '?';

		return count( $this->query() ) > 0
			? $this->url() . $question . Arr::query( array_merge( $this->query(), $query ) )
			: $this->full_url() . $question . Arr::query( $query );
	}

	/**
	 * Get the current path info for the request.
	 *
	 * @return string
	 */
	public function path() {
		$pattern = trim( $this->getPathInfo(), '/' );

		return '' === $pattern ? '/' : $pattern;
	}

	/**
	 * Get the current decoded path info for the request.
	 *
	 * @return string
	 */
	public function decoded_path() {
		return rawurldecode( $this->path() );
	}

	/**
	 * Get a segment from the URI (1 based index).
	 *
	 * @param  int         $index
	 * @param  string|null $default
	 * @return string|null
	 */
	public function segment( $index, $default = null ) {
		return Arr::get( $this->segments(), $index - 1, $default );
	}

	/**
	 * Get all of the segments for the request path.
	 *
	 * @return array
	 */
	public function segments() {
		$segments = explode( '/', $this->decoded_path() );

		return array_values(
			array_filter(
				$segments,
				fn ( $value ) => '' !== $value
			)
		);
	}

	/**
	 * Determine if the current request URI matches a pattern.
	 *
	 * @param  mixed ...$patterns
	 */
	public function is( ...$patterns ): bool {
		$path = $this->decoded_path();

		foreach ( $patterns as $pattern ) {
			if ( Str::is( $pattern, $path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if the current request URL and query string matches a pattern.
	 *
	 * @param  mixed ...$patterns
	 */
	public function full_url_is( ...$patterns ): bool {
		$url = $this->full_url();

		foreach ( $patterns as $pattern ) {
			if ( Str::is( $pattern, $url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if the request is the result of an AJAX call.
	 *
	 * @return bool
	 */
	public function ajax() {
		return $this->isXmlHttpRequest();
	}

	/**
	 * Determine if the request is the result of an PJAX call.
	 */
	public function pjax(): bool {
		return (bool) $this->headers->get( 'X-PJAX' );
	}

	/**
	 * Determine if the request is the result of an prefetch call.
	 */
	public function prefetch(): bool {
		return 0 === strcasecmp( (string) $this->server->get( 'HTTP_X_MOZ' ), 'prefetch' ) ||
			0 === strcasecmp( (string) $this->headers->get( 'Purpose' ), 'prefetch' );
	}

	/**
	 * Determine if the request is over HTTPS.
	 *
	 * @return bool
	 */
	public function secure() {
		return $this->isSecure();
	}

	/**
	 * Get the client IP address.
	 *
	 * @return string|null
	 */
	public function ip() {
		return $this->getClientIp();
	}

	/**
	 * Get the client IP addresses.
	 *
	 * @return array
	 */
	public function ips() {
		return $this->getClientIps();
	}

	/**
	 * Get the client user agent.
	 *
	 * @return string|null
	 */
	public function user_agent() {
		return $this->headers->get( 'User-Agent' );
	}

	/**
	 * Merge new input into the current request's input array.
	 *
	 * @param  array $input
	 * @return $this
	 */
	public function merge( array $input ) {
		$this->get_input_source()->add( $input );

		return $this;
	}

	/**
	 * Replace the input for the current request.
	 *
	 * @param  array $input
	 * @return $this
	 */
	public function replace( array $input ) {
		$this->get_input_source()->replace( $input );

		return $this;
	}

	/**
	 * This method belongs to Symfony HttpFoundation and is not usually needed.
	 *
	 * Instead, you may use the "input" method.
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 */
	public function get( string $key, mixed $default = null ): mixed { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		return parent::get( $key, $default );
	}

	/**
	 * Get the JSON payload for the request.
	 *
	 * @param  string|null $key
	 * @param  mixed       $default
	 * @return \Symfony\Component\HttpFoundation\ParameterBag|mixed
	 */
	public function json( $key = null, $default = null ) {
		if ( ! isset( $this->json ) ) {
			$this->json = new ParameterBag( (array) json_decode( $this->getContent(), true ) );
		}

		if ( is_null( $key ) ) {
			return $this->json;
		}

		return data_get( $this->json->all(), $key, $default );
	}

	/**
	 * Determine if the request is JSON.
	 */
	public function is_json(): bool {
		return $this->has_header( 'Content-Type' ) &&
			Str::contains( $this->header( 'Content-Type' ), 'json' );
	}

	/**
	 * Get the input source for the request.
	 *
	 * @return \Symfony\Component\HttpFoundation\ParameterBag
	 */
	protected function get_input_source() {
		if ( $this->is_json() ) {
			return $this->json();
		}

		return in_array( $this->getRealMethod(), [ 'GET', 'HEAD' ], true ) ? $this->query : $this->request;
	}

	/**
	 * Set the JSON payload for the request.
	 *
	 * @param  \Symfony\Component\HttpFoundation\ParameterBag $json
	 * @return $this
	 */
	public function set_json( $json ) {
		$this->json = $json;

		return $this;
	}

	/**
	 * Get all of the input and files for the request.
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->all();
	}

	/**
	 * Set route parameters.
	 *
	 * @param ParameterBag|array $parameters Route parameters to set.
	 * @return static
	 */
	public function set_route_parameters( $parameters ) {
		if ( ! ( $parameters instanceof ParameterBag ) ) {
			// Remove internal route parameters.
			$parameters = new ParameterBag(
				array_filter(
					$parameters,
					fn ( $parameter ) => ! str_starts_with( $parameter, '_' ),
					ARRAY_FILTER_USE_KEY
				)
			);
		}

		$this->route_parameters = $parameters;

		return $this;
	}

	/**
	 * Get route parameters.
	 */
	public function get_route_parameters(): ?ParameterBag {
		return $this->route_parameters;
	}

	/**
	 * Set a parameter to the given value.
	 *
	 * @param string $key Parameter to set.
	 * @param mixed  $value Value to set.
	 * @return static
	 */
	public function set_route_parameter( string $key, $value ) {
		$this->route_parameters->set( $key, $value );
		return $this;
	}

	/**
	 * Get the route.
	 *
	 * @return Route
	 */
	public function get_route(): ?Route {
		return $this->route ?? null;
	}

	/**
	 * Set a route match for the current request.
	 *
	 * @param Route $route Route instance to set.
	 * @return static
	 */
	public function set_route( Route $route ) {
		$this->route = $route;

		return $this;
	}

	/**
	 * Determine if the given offset exists.
	 *
	 * @param  mixed $offset
	 */
	public function offsetExists( mixed $offset ): bool {
		return Arr::has(
			$this->all() + $this->get_route_parameters()->all(),
			$offset
		);
	}

	/**
	 * Get the value at the given offset.
	 *
	 * @param  mixed $offset
	 */
	public function offsetGet( mixed $offset ): mixed {
		return $this->__get( $offset );
	}

	/**
	 * Set the value at the given offset.
	 *
	 * @param  mixed $offset
	 * @param  mixed $value
	 */
	public function offsetSet( mixed $offset, mixed $value ): void {
		$this->get_input_source()->set( $offset, $value );
	}

	/**
	 * Remove the value at the given offset.
	 *
	 * @param  mixed $offset
	 */
	public function offsetUnset( mixed $offset ): void {
		$this->get_input_source()->remove( $offset );
	}

	/**
	 * Check if an input element is set on the request.
	 *
	 * @param  string $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return ! is_null( $this->__get( $key ) );
	}

	/**
	 * Get an input element from the request.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return Arr::get(
			$this->all(),
			$key,
			fn () => $this->get_route_parameters()->get( $key )
		);
	}
}
