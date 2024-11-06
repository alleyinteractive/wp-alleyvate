<?php
/**
 * Model_Date_Proxy class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model\Dates;

use ArrayAccess;
use Carbon\Carbon;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Mantle\Database\Model\Post;
use Mantle\Support\Str;

/**
 * Allow post dates to be retrieved as an attribute on the object as Carbon instances.
 *
 * @property Carbon $created
 * @property Carbon $created_gmt
 * @property Carbon $created_utc
 * @property Carbon $modified
 * @property Carbon $modified_gmt
 * @property Carbon $modified_utc
 */
class Model_Date_Proxy implements ArrayAccess {
	/**
	 * Constructor.
	 *
	 * @param Post $model Model to reference.
	 */
	public function __construct( protected Post $model ) {}

	/**
	 * Retrieve model date by key.
	 *
	 * @throws InvalidArgumentException If the key is invalid.
	 *
	 * @param string $key Date key.
	 */
	public function __get( string $key ): Carbon {
		return match ( $key ) {
			'date', 'created', 'created_at' => Carbon::parse( $this->model->post_date, wp_timezone() ),
			'date_gmt', 'created_gmt', 'created_utc', 'created_at_gmt', 'created_at_utc' => Carbon::parse( $this->model->post_date_gmt, new DateTimeZone( 'UTC' ) ),
			'modified', 'modified_at', 'updated', 'updated_at' => Carbon::parse( $this->model->post_modified, wp_timezone() ),
			'modified_gmt', 'modified_utc', 'modified_at_gmt', 'modified_at_utc' => Carbon::parse( $this->model->post_modified_gmt, new DateTimeZone( 'UTC' ) ),
			default => throw new InvalidArgumentException( "Invalid date attribute: {$key}" ),
		};
	}

	/**
	 * Set model date attribute.
	 *
	 * @throws InvalidArgumentException If the key is invalid.
	 *
	 * @param string $key Date attribute.
	 * @param mixed  $value Date value.
	 */
	public function __set( string $key, $value ): void {
		$timezone = Str::ends_with( $key, [ '_gmt', '_utc' ] ) ? new DateTimeZone( 'UTC' ) : wp_timezone();

		if ( is_string( $value ) ) {
			$value = Carbon::parse( $value, $timezone );
		} elseif ( is_numeric( $value ) ) {
			$value = Carbon::createFromTimestamp( $value, $timezone );
		} elseif ( $value instanceof Carbon ) {
			$value->setTimezone( $timezone );
		} elseif ( $value instanceof DateTimeInterface ) {
			$value = Carbon::instance( $value )->setTimezone( $timezone );
		} else {
			throw new InvalidArgumentException( "Invalid date value for {$key}" );
		}

		match ( $key ) {
			'date', 'created', 'created_at' => $this->model->set_attribute( 'post_date', $value->format( 'Y-m-d H:i:s' ) ),
			'date_gmt', 'created_gmt', 'created_utc', 'created_at_gmt', 'created_at_utc' => $this->model->set_attribute( 'post_date_gmt', $value->format( 'Y-m-d H:i:s' ) ),
			'modified', 'modified_at', 'updated', 'updated_at' => $this->model->set_attribute( 'post_modified', $value->format( 'Y-m-d H:i:s' ) ),
			'modified_gmt', 'modified_utc', 'modified_at_gmt', 'modified_at_utc' => $this->model->set_attribute( 'post_modified_gmt', $value->format( 'Y-m-d H:i:s' ) ),
			default => throw new InvalidArgumentException( "Invalid date attribute: {$key}" ),
		};
	}

	/**
	 * Delete model date attribute.
	 *
	 * @throws InvalidArgumentException Upon use.
	 *
	 * @param string $key Date attribute.
	 */
	public function __unset( string $key ) {
		throw new InvalidArgumentException( 'Cannot unset model date attributes.' );
	}

	/**
	 * Check if a date exists.
	 *
	 * @param mixed $offset Date attribute.
	 */
	public function offsetExists( mixed $offset ): bool {
		try {
			$this->__get( $offset );
			return true;
		} catch ( InvalidArgumentException ) {
			return false;
		}
	}

	/**
	 * Retrieve the value of a model date attribute by key.
	 *
	 * @param mixed $offset Date attribute.
	 */
	public function offsetGet( mixed $offset ): mixed {
		return $this->__get( $offset );
	}

	/**
	 * Set the value of a model date attribute by key.
	 *
	 * @param mixed $offset Date attribute.
	 * @param mixed $value Value to set.
	 */
	public function offsetSet( mixed $offset, mixed $value ): void {
		$this->__set( $offset, $value );
	}

	/**
	 * Delete a model date attribute
	 *
	 * @param mixed $offset Date attribute.
	 */
	public function offsetUnset( mixed $offset ): void {
		$this->__unset( $offset );
	}
}
