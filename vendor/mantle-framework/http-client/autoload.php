<?php
/**
 * Mantle HTTP Client Helpers
 *
 * Intentionally not Namespaced to allow for root-level access to
 * framework methods.
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Squiz.Commenting.FunctionComment
 *
 * @package Mantle
 */

declare( strict_types=1 );

namespace Mantle\Http_Client;

use Mantle\Http_Client\Pending_Request;
use Mantle\Http_Client\Response;

if ( ! function_exists( 'http_client' ) ) {
	/**
	 * Create a new pending request of the the HTTP Client.
	 *
	 * @param string|null $url URL to request, optional.
	 * @return ($url is string ? Response : Pending_Request)
	 */
	function http_client( ?string $url = null ): Pending_Request|Response {
		return $url ? Pending_Request::create()->get( $url ) : Pending_Request::create();
	}
}
