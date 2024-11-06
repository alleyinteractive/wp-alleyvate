<?php
/**
 * Url_Generator class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use Mantle\Contracts\Http\Routing\Url_Generator as Generator_Contract;
use Mantle\Contracts\Http\Routing\Url_Routable;
use Mantle\Http\Request;
use Mantle\Support\Arr;
use Mantle\Support\Str;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * URL Generator
 *
 * Generates URLs to routes, paths, etc. on th e site.
 */
class Url_Generator extends UrlGenerator implements Generator_Contract {
	/**
	 * The request instance.
	 *
	 * @var Request
	 */
	protected $request;

	/**
	 * The forced scheme for URLs.
	 */
	protected ?string $force_scheme = null;

	/**
	 * A cached copy of the URL root for the current request.
	 */
	protected ?string $cached_root = null;

	/**
	 * A cached copy of the URL scheme for the current request.
	 */
	protected ?string $cached_scheme = null;

	/**
	 * Constructor.
	 *
	 * @param string               $root_url Root URL.
	 * @param RouteCollection      $routes Route collection.
	 * @param Request              $request Request object.
	 * @param LoggerInterface|null $logger Logger instance.
	 */
	public function __construct( protected string $root_url, RouteCollection $routes, Request $request, LoggerInterface $logger = null ) {
		$this->root_url = $root_url;
		$this->routes   = $routes;
		$this->logger   = $logger;

		$this->set_request( $request );
	}

	/**
	 * Set the request object.
	 *
	 * @param Request $request Request object to set.
	 * @return static
	 */
	public function set_request( Request $request ) {
		$this->request = $request;
		$this->context = ( new RequestContext() )->fromRequest( $request );

		// Set the host for the request context if it is not already set.
		if ( empty( $this->context->getHost() ) ) {
			$this->context->setHost( (string) parse_url( $this->root_url, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		}

		if ( ! $this->context->hasParameter( '_locale' ) ) {
			$this->context->setParameter( '_locale', 'en' );
		}

		return $this;
	}

	/**
	 * Get the request object.
	 */
	public function get_request(): Request {
		return $this->request;
	}

	/**
	 * Get the current URL for the request.
	 *
	 * @return string
	 */
	public function current() {
		return $this->to( $this->request->getPathInfo() );
	}

	/**
	 * Get the URL for the previous request.
	 *
	 * @param string $fallback Fallback value, optional.
	 */
	public function previous( string $fallback = null ): string {
		return $this->to(
			$this->request->headers->get( 'referer', $fallback ?? '/' )
		);
	}

	/**
	 * Generate a URL to a specific path.
	 *
	 * @param string               $path URL Path.
	 * @param array<string, mixed> $extra_query Extra query parameters to be appended to the URL path.
	 * @param array                $extra_params Extra parameters to be appended to the URL path.
	 * @param bool                 $secure Flag if should be forced to be secure.
	 * @return string
	 */
	public function to( string $path, array $extra_query = [], array $extra_params = [], bool $secure = null ) {
		// First we will check if the URL is already a valid URL. If it is we will not
		// try to generate a new one but will simply return the URL as is, which is
		// convenient since developers do not always have to check if it's valid.
		if ( $this->is_valid_url( $path ) ) {
			return $path;
		}

		$tail = implode(
			'/',
			array_map(
				'rawurlencode',
				$this->format_parameters( $extra_params )
			)
		);

		// Once we have the scheme we will compile the "tail" by collapsing the values
		// into a single string delimited by slashes. This just makes it convenient
		// for passing the array of parameters to this URL as a list of segments.
		$root = $this->format_root( $this->format_scheme( $secure ) );

		[ $path, $query ] = $this->extract_query_string( $path );

		// Append the tail to the path while preserving the trailing slash on the path.
		if ( ! empty( $tail ) ) {
			$path = rtrim( $path, '/' ) . '/' . $tail . ( Str::ends_with( $path, '/' ) ? '/' : '' );
		}

		$url = $this->format( $root, $path ) . $query;

		// Append any extra query parameters.
		if ( ! empty( $extra_query ) ) {
			$extra_query = Arr::query( $extra_query );

			$url .= ( Str::contains( $url, '?' ) ? '&' : '?' ) . $extra_query;
		}

		return $url;
	}

	/**
	 * Generate a URL for a route.
	 *
	 * @param string $name Route name.
	 * @param array  $parameters Route parameters.
	 * @param bool   $absolute Flag if should be absolute.
	 *
	 * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException If route not found.
	 */
	public function route( string $name, array $parameters = [], bool $absolute = true ): string {
		return $this->generate(
			$name,
			$parameters,
			$absolute ? self::ABSOLUTE_URL : self::ABSOLUTE_PATH,
		);
	}

	/**
	 * Format the array of URL parameters.
	 *
	 * @param  mixed|array $parameters
	 */
	public function format_parameters( $parameters ): array {
		$parameters = Arr::wrap( $parameters );

		foreach ( $parameters as $key => $parameter ) {
			if ( $parameter instanceof Url_Routable ) {
				$parameters[ $key ] = $parameter->get_route_key();
			}
		}

		return $parameters;
	}

	/**
	 * Get the default scheme for a raw URL.
	 *
	 * @param  bool|null $secure Flag if should be secure.
	 */
	public function format_scheme( $secure = null ): string {
		if ( ! is_null( $secure ) ) {
			return $secure ? 'https://' : 'http://';
		}

		if ( empty( $this->cached_scheme ) ) {
			$this->cached_scheme = $this->force_scheme ?: $this->context->getScheme() . '://';
		}

		return $this->cached_scheme;
	}

	/**
	 * Extract the query string from the given path.
	 *
	 * @param  string $path URL Path.
	 * @return array<int, string>
	 */
	protected function extract_query_string( $path ) {
		$query_position = strpos( $path, '?' );
		if ( false !== $query_position ) {
			return [
				substr( $path, 0, $query_position ),
				substr( $path, $query_position ),
			];
		}

		return [ $path, '' ];
	}

	/**
	 * Get the root URL.
	 */
	public function get_root_url(): string {
		return $this->root_url;
	}

	/**
	 * Set the root URL.
	 *
	 * @param string $url Root URL to set.
	 * @return static
	 */
	public function root_url( string $url ) {
		$this->cached_root = null;

		$this->root_url = $url;

		$this->context->setHost( parse_url( $url, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url

		return $this;
	}

	/**
	 * Get the base URL for the request.
	 *
	 * @param  string      $scheme
	 * @param  string|null $root
	 */
	public function format_root( string $scheme, ?string $root = null ): string {
		if ( is_null( $root ) ) {
			$root = $this->root_url;
		}

		$start = str_starts_with( $root, 'http://' ) ? 'http://' : 'https://';

		return preg_replace( '~' . $start . '~', $scheme, $root, 1 );
	}

	/**
	 * Format the given URL segments into a single URL.
	 *
	 * Preserves the path's trailing slash if present.
	 *
	 * @param  string $root URL root.
	 * @param  string $path URL path.
	 */
	public function format( string $root, string $path ): string {
		return trim( rtrim( $root, '/' ) . '/' . ltrim( $path, '/' ) );
	}

	/**
	 * Determine if the given path is a valid URL.
	 *
	 * @param  string $path
	 */
	public function is_valid_url( $path ): bool {
		if ( ! preg_match( '~^(#|//|https?://|(mailto|tel|sms):)~', $path ) ) {
			return filter_var( $path, FILTER_VALIDATE_URL ) !== false;
		}

		return true;
	}

	/**
	 * Force the scheme for URLs.
	 *
	 * @param  string $scheme
	 */
	public function force_scheme( string $scheme ): void {
		$this->cached_scheme = null;

		$this->force_scheme = $scheme . '://';
	}

	/**
	 * Set the routes in the generator.
	 *
	 * @param RouteCollection $routes Route collection.
	 * @return static
	 */
	public function set_routes( RouteCollection $routes ) {
		$this->routes = $routes;

		return $this;
	}
}
