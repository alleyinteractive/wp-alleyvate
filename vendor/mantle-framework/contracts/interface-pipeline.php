<?php
/**
 * Pipeline interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts;

use Closure;

/**
 * Pipeline Contract
 */
interface Pipeline {

	/**
	 * Set the traveler object being sent on the pipeline.
	 *
	 * @param  mixed $traveler
	 * @return $this
	 */
	public function send( $traveler );

	/**
	 * Set the stops of the pipeline.
	 *
	 * @param  array<callable>|null $stops
	 * @return $this
	 */
	public function through( $stops );

	/**
	 * Set the method to call on the stops.
	 *
	 * @param  string $method
	 * @return $this
	 */
	public function via( $method );

	/**
	 * Run the pipeline with a final destination callback.
	 *
	 * @param  \Closure $destination
	 * @return mixed
	 */
	public function then( Closure $destination );
}
