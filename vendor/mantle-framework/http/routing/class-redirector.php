<?php
/**
 * Redirector class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Redirector Response
 */
class Redirector {
	/**
	 * Status code for a permanent redirect.
	 *
	 * @var int
	 */
	public const STATUS_PERMANENT = 301;

	/**
	 * Status code for a temporary redirect.
	 *
	 * @var int
	 */
	public const STATUS_TEMPORARY = 302;

	/**
	 * URL Generator instance.
	 *
	 * @var Url_Generator
	 */
	protected $generator;

	/**
	 * Constructor.
	 *
	 * @param Url_Generator $generator Generator instance.
	 */
	public function __construct( Url_Generator $generator ) {
		$this->generator = $generator;
	}

	/**
	 * Generate a redirect to the homepage of the site.
	 *
	 * @param int   $status Status code.
	 * @param array $headers Additional headers.
	 */
	public function home( int $status = self::STATUS_TEMPORARY, array $headers = [] ): RedirectResponse {
		return $this->to( $this->generator->to( '/', [], [], null ), $status, $headers );
	}

	/**
	 * Generate a redirect to the previous page the user was on.
	 *
	 * @param int    $status Status code.
	 * @param array  $headers Additional headers.
	 * @param string $fallback Fallback URL.
	 */
	public function back( int $status = self::STATUS_TEMPORARY, array $headers = [], string $fallback = null ): RedirectResponse {
		return $this->to( $this->generator->previous( $fallback ), $status, $headers );
	}

	/**
	 * Generate a redirect to the current URL.
	 *
	 * @param int   $status Status code.
	 * @param array $headers Additional headers.
	 */
	public function refresh( int $status = self::STATUS_TEMPORARY, array $headers = [] ): RedirectResponse {
		return $this->to( $this->generator->get_request()->path(), $status, $headers );
	}

	/**
	 * Generate a redirect to a specific path on the current site.
	 *
	 * @param string $path URL path to redirect to.
	 * @param int    $status Status code.
	 * @param array  $headers Additional headers.
	 * @param bool   $secure Flag if the redirect should be secured with HTTPS.
	 */
	public function to( string $path, int $status = self::STATUS_TEMPORARY, array $headers = [], bool $secure = null ): RedirectResponse {
		return $this->create_redirect(
			$this->generator->to( $path, [], [], $secure ),
			$status,
			$headers
		);
	}

	/**
	 * Generate a redirect to a URL off the site.
	 *
	 * @param string $url URL to redirect to.
	 * @param int    $status Status code.
	 * @param array  $headers Additional headers.
	 */
	public function away( string $url, int $status = self::STATUS_TEMPORARY, array $headers = [] ): RedirectResponse {
		return $this->create_redirect(
			$url,
			$status,
			$headers
		);
	}

	/**
	 * Generate a secure redirect.
	 *
	 * @param string $path URL path to redirect to.
	 * @param int    $status Status code.
	 * @param array  $headers Additional headers.
	 */
	public function secure( string $path, int $status = self::STATUS_TEMPORARY, array $headers = [] ): RedirectResponse {
		return $this->to( $path, $status, $headers, true );
	}

	/**
	 * Generate a redirect to a specific route.
	 *
	 * @param string $route Route to generate to.
	 * @param array  $parameters Parameters for the route.
	 * @param int    $status Status code.
	 * @param array  $headers Additional headers.
	 */
	public function route( string $route, array $parameters = [], int $status = self::STATUS_TEMPORARY, array $headers = [] ): RedirectResponse {
		return $this->to( $this->generator->generate( $route, $parameters ), $status, $headers );
	}

	/**
	 * Create a redirect response.
	 *
	 * @param string $path URL path.
	 * @param int    $status HTTP status code.
	 * @param array  $headers Array of headers, optional.
	 */
	protected function create_redirect( string $path, int $status, array $headers = [] ): RedirectResponse {
		return new RedirectResponse( $path, $status, $headers );
	}
}
