<?php
/**
 * Response_Factory class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing;

use Mantle\Contracts\Http\Routing\Response_Factory as Factory_Contract;
use Mantle\Http\Response;
use Mantle\Http\View\Factory as View_Factory;
use Mantle\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Route Response Factory.
 */
class Response_Factory implements Factory_Contract {
	/**
	 * The redirector instance.
	 *
	 * @var Redirector
	 */
	protected $redirector;

	/**
	 * The view factory instance.
	 *
	 * @var View_Factory
	 */
	protected $view;

	/**
	 * Create a new response factory instance.
	 *
	 * @param Redirector   $redirector Redirector instance.
	 * @param View_Factory $view View factory.
	 */
	public function __construct( Redirector $redirector, View_Factory $view ) {
		$this->redirector = $redirector;
		$this->view       = $view;
	}

	/**
	 * Create a new response instance.
	 *
	 * @param  string $content
	 * @param  int    $status
	 * @param  array  $headers
	 * @return Response
	 */
	public function make( $content = '', $status = 200, array $headers = [] ) {
		return new Response( $content, $status, $headers );
	}

	/**
	 * Create a new "no content" response.
	 *
	 * @param  int   $status
	 * @param  array $headers
	 * @return Response
	 */
	public function no_content( $status = 204, array $headers = [] ) {
		return $this->make( '', $status, $headers );
	}

	/**
	 * Create a new response for a given view.
	 *
	 * @param  string $slug View slug.
	 * @param  string $name View name (optional).
	 * @param  array  $data Data to pass to the view.
	 * @param  int    $status HTTP status code.
	 * @param  array  $headers Additional headers.
	 * @return Response
	 */
	public function view( string $slug, $name = null, $data = [], $status = 200, array $headers = [] ) {
		return $this->make(
			$this->view->make( $slug, $name, $data ),
			$status,
			$headers
		);
	}

	/**
	 * Create a new JSON response instance.
	 *
	 * @param  mixed $data
	 * @param  int   $status
	 * @param  array $headers
	 * @return JsonResponse
	 */
	public function json( $data = [], $status = 200, array $headers = [] ) {
		return new JsonResponse( $data, $status, $headers, is_string( $data ) );
	}

	/**
	 * Create a new JSONP response instance.
	 *
	 * @param  string $callback
	 * @param  mixed  $data
	 * @param  int    $status
	 * @param  array  $headers
	 * @return JsonResponse
	 */
	public function jsonp( $callback, $data = [], $status = 200, array $headers = [] ) {
		return $this->json( $data, $status, $headers )->setCallback( $callback );
	}

	/**
	 * Create a new streamed response instance.
	 *
	 * @param  \Closure $callback
	 * @param  int      $status
	 * @param  array    $headers
	 * @return \Symfony\Component\HttpFoundation\StreamedResponse
	 */
	public function stream( $callback, $status = 200, array $headers = [] ) {
		return new StreamedResponse( $callback, $status, $headers );
	}

	/**
	 * Create a new streamed response instance as a file download.
	 *
	 * @param  \Closure    $callback
	 * @param  string|null $name
	 * @param  array       $headers
	 * @param  string|null $disposition
	 * @return \Symfony\Component\HttpFoundation\StreamedResponse
	 */
	public function stream_download( $callback, $name = null, array $headers = [], $disposition = 'attachment' ) {
		$response = new StreamedResponse( $callback, 200, $headers );

		if ( ! is_null( $name ) ) {
			$response->headers->set(
				'Content-Disposition',
				$response->headers->makeDisposition(
					$disposition,
					$name,
					$this->fallbackName( $name )
				)
			);
		}

		return $response;
	}

	/**
	 * Create a new file download response.
	 *
	 * @param  \SplFileInfo|string $file
	 * @param  string|null         $name
	 * @param  array               $headers
	 * @param  string|null         $disposition
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public function download( $file, $name = null, array $headers = [], $disposition = 'attachment' ) {
		$response = new BinaryFileResponse( $file, 200, $headers, true, $disposition );

		if ( ! is_null( $name ) ) {
			return $response->setContentDisposition( $disposition, $name, $this->fallbackName( $name ) );
		}

		return $response;
	}

	/**
	 * Convert the string to ASCII characters that are equivalent to the given name.
	 *
	 * @param  string $name
	 * @return string
	 */
	protected function fallbackName( $name ) {
		return str_replace( '%', '', Str::ascii( $name ) );
	}

	/**
	 * Return the raw contents of a binary file.
	 *
	 * @param  \SplFileInfo|string $file
	 * @param  array               $headers
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public function file( $file, array $headers = [] ) {
		return new BinaryFileResponse( $file, 200, $headers );
	}

	/**
	 * Create a new redirect response to the given path.
	 *
	 * @param  string    $path
	 * @param  int       $status
	 * @param  array     $headers
	 * @param  bool|null $secure
	 */
	public function redirect_to( $path, $status = 302, $headers = [], $secure = null ): RedirectResponse {
		return $this->redirector->to( $path, $status, $headers, $secure );
	}

	/**
	 * Create a new redirect response to a named route.
	 *
	 * @param  string $route
	 * @param  mixed  $parameters
	 * @param  int    $status
	 * @param  array  $headers
	 */
	public function redirect_to_route( $route, $parameters = [], $status = 302, $headers = [] ): RedirectResponse {
		return $this->redirector->route( $route, $parameters, $status, $headers );
	}
}
