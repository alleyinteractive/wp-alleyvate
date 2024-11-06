<?php
/**
 * Model_Meta_Proxy class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Meta;

use ArrayAccess;
use Mantle\Contracts\Database\Model_Meta;

/**
 * Allow meta to be retrieved as an attribute on the object.
 */
class Model_Meta_Proxy implements ArrayAccess {
	/**
	 * Constructor.
	 *
	 * @param Model_Meta $model Model to reference.
	 */
	public function __construct( protected Model_Meta $model ) {}

	/**
	 * Retrieve model meta by key.
	 *
	 * @param string $key Meta key.
	 * @return mixed
	 */
	public function __get( string $key ) {
		$queued_value = $this->model->get_queued_meta_attribute( $key );
		if ( null !== $queued_value ) {
			return $queued_value;
		}

		return $this->model->get_meta( $key );
	}

	/**
	 * Set model meta.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 */
	public function __set( string $key, $value ) {
		$this->model->queue_meta_attribute( $key, $value );
	}

	/**
	 * Delete model meta.
	 *
	 * @param string $key Meta key.
	 */
	public function __unset( string $key ) {
		$this->model->delete_meta( $key );
	}

	/**
	 * Check if model meta exists.
	 *
	 * @param mixed $offset Meta key.
	 */
	public function offsetExists( mixed $offset ): bool {
		return null !== $this->model->get_meta( $offset );
	}

	/**
	 * Retrieve the value of a model meta by key.
	 *
	 * @param mixed $offset Meta key.
	 */
	public function offsetGet( mixed $offset ): mixed {
		return $this->__get( $offset );
	}

	/**
	 * Set the value of a model meta.
	 *
	 * @param mixed $offset Meta key.
	 * @param mixed $value Meta value.
	 */
	public function offsetSet( mixed $offset, mixed $value ): void {
		$this->__set( $offset, $value );
	}

	/**
	 * Delete a model meta.
	 *
	 * @param mixed $offset Meta key.
	 */
	public function offsetUnset( mixed $offset ): void {
		$this->__unset( $offset );
	}
}
