<?php
/**
 * Prevent_Remote_Requests trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Testing\Mock_Http_Response;

/**
 * Prevent remote requests from being made by providing a default response to
 * the remote request.
 *
 * @mixin \Mantle\Testing\Test_Case
 */
trait Prevent_Remote_Requests {
	/**
	 * Setup the trait.
	 */
	public function prevent_remote_requests_set_up(): void {
		if ( ! $this->prevent_remote_requests ) {
			$this->prevent_stray_requests( new Mock_Http_Response() );
		}
	}
}
