<?php
/**
 * Controller class file.
 *
 * @package Mantle
 */

namespace Mantle\Http;

use BadMethodCallException;

/**
 * Base Controller Class
 */
abstract class Controller {
	/**
	 * Execute an action on the controller.
	 *
	 * @param string $method Method to call.
	 * @param array  $parameters Parameters to include.
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function call_action( string $method, array $parameters ) {
		return $this->{ $method }( ...array_values( $parameters ) );
	}

	/**
	 * Handle calls to missing methods on the controller.
	 *
	 * @param string $method Method name.
	 * @param array  $parameters Method parameters.
	 * @return mixed
	 *
	 * @throws BadMethodCallException Thrown on unknown exception.
	 */
	public function __call( string $method, array $parameters ) {
		throw new BadMethodCallException(
			sprintf(
				'Method %s::%s does not exist.',
				static::class,
				$method
			)
		);
	}
}
