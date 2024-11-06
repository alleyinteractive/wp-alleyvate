<?php
/**
 * Response_Factory interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Http\Routing;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Mantle\Http\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Response Factory Contract
 */
interface Response_Factory {

	/**
	 * Create a new response instance.
	 *
	 * @param  string $content
	 * @param  int    $status
	 * @param  array  $headers
	 * @return Response
	 */
	public function make( $content = '', $status = 200, array $headers = [] );

	/**
	 * Create a new "no content" response.
	 *
	 * @param  int   $status
	 * @param  array $headers
	 * @return Response
	 */
	public function no_content( $status = 204, array $headers = [] );

	/**
	 * Create a new response for a given view.
	 *
	 * @param string $slug View slug.
	 * @param string $name View name (optional).
	 * @param array  $data Data to pass to the view.
	 * @param int    $status Response status code.
	 * @param array  $headers Additional headers.
	 * @return Response
	 */
	public function view( string $slug, $name = null, $data = [], $status = 200, array $headers = [] );

	/**
	 * Create a new JSON response instance.
	 *
	 * @param  mixed $data
	 * @param  int   $status
	 * @param  array $headers
	 * @return JsonResponse
	 */
	public function json( $data = [], $status = 200, array $headers = [] );

	/**
	 * Create a new JSONP response instance.
	 *
	 * @param  string $callback
	 * @param  mixed  $data
	 * @param  int    $status
	 * @param  array  $headers
	 * @return JsonResponse
	 */
	public function jsonp( $callback, $data = [], $status = 200, array $headers = [] );

	/**
	 * Create a new streamed response instance.
	 *
	 * @param  \Closure $callback
	 * @param  int      $status
	 * @param  array    $headers
	 * @return \Symfony\Component\HttpFoundation\StreamedResponse
	 */
	public function stream( $callback, $status = 200, array $headers = [] );

	/**
	 * Create a new streamed response instance as a file download.
	 *
	 * @param  \Closure    $callback
	 * @param  string|null $name
	 * @param  array       $headers
	 * @param  string|null $disposition
	 * @return \Symfony\Component\HttpFoundation\StreamedResponse
	 */
	public function stream_download( $callback, $name = null, array $headers = [], $disposition = 'attachment' );

	/**
	 * Create a new file download response.
	 *
	 * @param  \SplFileInfo|string $file
	 * @param  string|null         $name
	 * @param  array               $headers
	 * @param  string|null         $disposition
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public function download( $file, $name = null, array $headers = [], $disposition = 'attachment' );

	/**
	 * Return the raw contents of a binary file.
	 *
	 * @param  \SplFileInfo|string $file
	 * @param  array               $headers
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public function file( $file, array $headers = [] );

	/**
	 * Create a new redirect response to the given path.
	 *
	 * @param  string    $path
	 * @param  int       $status
	 * @param  array     $headers
	 * @param  bool|null $secure
	 */
	public function redirect_to( $path, $status = 302, $headers = [], $secure = null ): RedirectResponse;

	/**
	 * Create a new redirect response to a named route.
	 *
	 * @param  string $route
	 * @param  mixed  $parameters
	 * @param  int    $status
	 * @param  array  $headers
	 */
	public function redirect_to_route( $route, $parameters = [], $status = 302, $headers = [] ): RedirectResponse;
}
