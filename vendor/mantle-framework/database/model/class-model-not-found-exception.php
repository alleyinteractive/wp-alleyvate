<?php
/**
 * Model_Not_Found_Exception class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Mantle\Support\Arr;

/**
 * Model Not Found Exception
 */
class Model_Not_Found_Exception extends Model_Exception {
	/**
	 * Constructor.
	 *
	 * @param array|string $model Name of the affected Eloquent model(s).
	 * @param array        $ids Model ID(s).
	 */
	public function __construct( public array|string $model, public array $ids = [] ) {
		$this->set_message();
	}

	/**
	 * Set the affected Eloquent model and instance ids.
	 *
	 * @param string    $model Model name.
	 * @param int|array $ids Model ID(s).
	 */
	public function set_model( string $model, $ids = [] ): static {
		$this->model = $model;
		$this->ids   = Arr::wrap( $ids );

		$this->set_message();

		return $this;
	}

	/**
	 * Get the affected Eloquent model.
	 */
	public function get_model(): string {
		return $this->model;
	}

	/**
	 * Get the affected Eloquent model IDs.
	 *
	 * @return int|array
	 */
	public function get_ids() {
		return $this->ids;
	}

	/**
	 * Set the exception message.
	 */
	protected function set_message(): void {
		$model = is_array( $this->model ) ? implode( ', ', $this->model ) : $this->model;

		$this->message = "No query results for model [{$model}]";

		if ( count( $this->ids ) > 0 ) {
			$this->message .= ' ' . implode( ', ', $this->ids );
		} else {
			$this->message .= '.';
		}
	}
}
