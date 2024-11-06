<?php
/**
 * Model_Meta class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Meta;

use BackedEnum;
use Mantle\Database\Model\Model_Exception;

/**
 * Interface for interfacing with a model's meta.
 *
 * @property object $meta
 * @property array<string, mixed> $queued_meta
 */
trait Model_Meta {
	/**
	 * Meta queued for saving.
	 *
	 * @var array
	 */
	protected $queued_meta = [];

	/**
	 * Retrieve meta data for the object.
	 *
	 * @param string $meta_key Meta key to retrieve.
	 * @param bool   $single Return the first meta key, defaults to true.
	 */
	public function get_meta( string $meta_key, bool $single = true ): mixed {
		return \get_metadata( $this->get_meta_type(), $this->id(), $meta_key, $single );
	}

	/**
	 * Add meta value for the object.
	 *
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value to store.
	 */
	public function add_meta( string $meta_key, mixed $meta_value ): void {
		if ( ! $this->id() ) {
			$this->queue_meta_attribute( $meta_key, $meta_value, false );
			return;
		}

		\add_metadata( $this->get_meta_type(), $this->id(), $meta_key, $this->serialize_value_for_storage( $meta_value ) );
	}

	/**
	 * Update meta value for the object.
	 *
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value to store.
	 * @param string $prev_value Optional, previous meta value.
	 */
	public function set_meta( string $meta_key, mixed $meta_value, mixed $prev_value = '' ): void {
		if ( ! $this->id() ) {
			$this->queue_meta_attribute( $meta_key, $meta_value );
			return;
		}

		\update_metadata( $this->get_meta_type(), $this->id(), $meta_key, $this->serialize_value_for_storage( $meta_value ), $prev_value );
	}

	/**
	 * Delete a object's meta.
	 *
	 * @param string $meta_key Meta key to delete by.
	 * @param mixed  $meta_value Previous meta value to delete.
	 */
	public function delete_meta( string $meta_key, mixed $meta_value = '' ): void {
		\delete_metadata( $this->get_meta_type(), $this->id(), $meta_key, $this->serialize_value_for_storage( $meta_value ) );
	}

	/**
	 * Retrieve the meta 'attribute'.
	 *
	 * @return Model_Meta_Proxy
	 */
	public function get_meta_attribute() {
		return new Model_Meta_Proxy( $this );
	}

	/**
	 * Allow setting meta through an array via an attribute mutator.
	 *
	 * @param array $meta_values Meta values to set.
	 * @throws Model_Exception Thrown on invalid value being set.
	 */
	public function set_meta_attribute( $meta_values ): void {
		if ( ! is_array( $meta_values ) ) {
			throw new Model_Exception( 'Attribute value passed to meta is not an array.' );
		}

		foreach ( $meta_values as $key => $value ) {
			$this->queued_meta[ $key ] = $value;
			$this->set_meta( $key, $value );
		}
	}

	/**
	 * Get a queued meta attribute.
	 *
	 * @param string $key Meta key.
	 * @return mixed|null Meta value or null.
	 */
	public function get_queued_meta_attribute( string $key ): mixed {
		return ( $this->queued_meta[ $key ] ?? [] )[0] ?? null;
	}

	/**
	 * Queue a meta attribute for saving.
	 * Allows meta to be set before a model is saved.
	 *
	 * Should not be called directly, only to be used via `$model->meta->...`.
	 *
	 * @param string $key Meta key.
	 * @param mixed  $value Meta value.
	 * @param bool   $update Flag to update the queued meta.
	 */
	public function queue_meta_attribute( string $key, $value, bool $update = true ): void {
		$this->queued_meta[ $key ] = [ $value, $update ];
	}

	/**
	 * Store queued model meta.
	 */
	public function store_queued_meta(): void {
		foreach ( $this->queued_meta as $key => [ $value, $update ] ) {
			if ( $update ) {
				$this->set_meta( $key, $value );
			} else {
				$this->add_meta( $key, $value );
			}
		}

		$this->queued_meta = [];
	}

	/**
	 * Serialize meta value for storage, converting all backed enums to their value.
	 *
	 * @param mixed $value Value to serialize.
	 */
	protected function serialize_value_for_storage( mixed $value ): mixed {
		if ( $value instanceof BackedEnum ) {
			return $value->value;
		}

		return $value;
	}
}
