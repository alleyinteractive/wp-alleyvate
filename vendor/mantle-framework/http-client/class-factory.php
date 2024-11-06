<?php
/**
 * Factory class file
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

use Mantle\Support\Traits\Macroable;

/**
 * Http Client factory.
 *
 * @mixin \Mantle\Http_Client\Pending_Request
 */
class Factory {
	use Macroable {
		__call as macro_call;
	}

	/**
	 * Create a new pending request.
	 */
	public static function create(): Pending_Request {
		return ( new static() )->new_pending_request();
	}

	/**
	 * Generate a new pending request.
	 */
	protected function new_pending_request(): Pending_Request {
		return new Pending_Request();
	}

	/**
	 * Forward the call to a new pending request.
	 *
	 * @param string $method Method name.
	 * @param array  $parameters Method parameters.
	 * @return Response|Pending_Request|mixed
	 */
	public function __call( string $method, array $parameters ) {
		if ( static::has_macro( $method ) ) {
			return $this->macro_call( $method, $parameters );
		}

		return $this->new_pending_request()->{$method}( ...$parameters );
	}

	/**
	 * Forward a static call to a new pending request.
	 *
	 * @param string $method Method name.
	 * @param array  $parameters Method parameters.
	 * @return Response|Pending_Request|Pool|mixed
	 */
	public static function __callStatic( string $method, array $parameters ) {
		if ( static::has_macro( $method ) ) {
			return ( new static() )->macro_call( $method, $parameters );
		}

		return ( new static() )->{$method}( ...$parameters );
	}
}
