<?php
/**
 * Bindings_Substituted class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing\Events;

use Mantle\Http\Request;

/**
 * Event for when bindings are substituted for a request.
 */
class Bindings_Substituted {
	/**
	 * Request instance.
	 *
	 * @var Request
	 */
	public $request;

	/**
	 * Constructor.
	 *
	 * @param Request $request Request instance.
	 */
	public function __construct( Request $request ) {
		$this->request = $request;
	}
}
