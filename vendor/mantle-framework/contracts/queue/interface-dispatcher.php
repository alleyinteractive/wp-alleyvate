<?php
/**
 * Dispatcher interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Queue;

/**
 * Queue Dispatcher
 */
interface Dispatcher {
	/**
	 * Dispatch the job to the queue to be handled asynchronously.
	 *
	 * @param mixed $job Job instance.
	 * @return mixed
	 */
	public function dispatch( $job );

	/**
	 * Dispatch the job to the queue to be executed now.
	 *
	 * @param mixed $job Job instance.
	 * @return mixed
	 */
	public function dispatch_now( $job );
}
