<?php
/**
 * Provider interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Queue;

use Mantle\Support\Collection;

/**
 * Queue Provider Contract
 */
interface Provider {
	/**
	 * Push a job to the queue.
	 *
	 * @param mixed $job Job instance.
	 */
	public function push( $job ): bool;

	/**
	 * Get the next set of jobs in the queue.
	 *
	 * @param string $queue Queue name, optional.
	 * @param int    $count Number of items to return.
	 */
	public function pop( string $queue = null, int $count = 1 ): Collection;

	/**
	 * Check if a job is in the queue.
	 *
	 * @param mixed  $job Job instance.
	 * @param string $queue Queue to compare against.
	 */
	public function in_queue( mixed $job, string $queue = null ): bool;

	/**
	 * Retrieve the number of pending jobs in the queue.
	 *
	 * @param string $queue Queue name, optional.
	 */
	public function pending_count( string $queue = null ): int;
}
