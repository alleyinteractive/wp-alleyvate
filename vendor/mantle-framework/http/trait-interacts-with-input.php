<?php
/**
 * Interacts_With_Input trait file.
 *
 * @phpcs:disable Squiz.Commenting.FunctionComment.MissingParamComment
 *
 * @package Mantle
 */

namespace Mantle\Http;

use Mantle\Http\Uploaded_File;
use Mantle\Support\Arr;
use Mantle\Support\Str;
use stdClass;

use function Mantle\Support\Helpers\data_get;

/**
 * Interacts With Input trait.
 */
trait Interacts_With_Input {

	/**
	 * Retrieve a server variable from the request.
	 *
	 * @param  string|null       $key
	 * @param  string|array|null $default
	 * @return string|array|null
	 */
	public function server( $key = null, $default = null ) {
		return $this->retrieve_item( 'server', $key, $default );
	}

	/**
	 * Determine if a header is set on the request.
	 *
	 * @param  string $key
	 * @return bool
	 */
	public function has_header( $key ) {
		return ! is_null( $this->header( $key ) );
	}

	/**
	 * Retrieve a header from the request.
	 *
	 * @param  string|null       $key
	 * @param  string|array|null $default
	 */
	public function header( $key = null, $default = null ): string|array|null {
		return $this->retrieve_item( 'headers', $key, $default );
	}

	/**
	 * Get the bearer token from the request headers.
	 */
	public function bearer_token(): ?string {
		$header = $this->header( 'Authorization', '' );

		if ( is_array( $header ) ) {
			$header = Arr::first( $header );
		}

		if ( Str::starts_with( $header, 'Bearer ' ) ) {
			return Str::substr( $header, 7 );
		}

		return $header;
	}

	/**
	 * Determine if the request contains a given input item key.
	 *
	 * @param  string|array $key
	 */
	public function exists( $key ): bool {
		return $this->has( $key );
	}

	/**
	 * Determine if the request contains a given input item key.
	 *
	 * @param  string|array $key
	 */
	public function has( $key ): bool {
		$keys = is_array( $key ) ? $key : func_get_args();

		$input = $this->all();

		foreach ( $keys as $key ) {
			if ( ! Arr::has( $input, $key ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine if the request contains any of the given inputs.
	 *
	 * @param  string|array $keys
	 * @return bool
	 */
	public function has_any( $keys ) {
		$keys = is_array( $keys ) ? $keys : func_get_args();

		$input = $this->all();

		return Arr::has_any( $input, $keys );
	}

	/**
	 * Determine if the request contains a non-empty value for an input item.
	 *
	 * @param  string|array $key
	 */
	public function filled( $key ): bool {
		$keys = is_array( $key ) ? $key : func_get_args();

		foreach ( $keys as $key ) {
			if ( $this->is_empty_string( $key ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine if the request contains a non-empty value for any of the given inputs.
	 *
	 * @param  string|array $keys
	 */
	public function any_filled( $keys ): bool {
		$keys = is_array( $keys ) ? $keys : func_get_args();

		foreach ( $keys as $key ) {
			if ( $this->filled( $key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if the request is missing a given input item key.
	 *
	 * @param  string|array $key
	 * @return bool
	 */
	public function missing( $key ) {
		$keys = is_array( $key ) ? $key : func_get_args();

		return ! $this->has( $keys );
	}

	/**
	 * Determine if the given input key is an empty string for "has".
	 *
	 * @param  string $key
	 */
	protected function is_empty_string( $key ): bool {
		$value = $this->input( $key );

		return ! is_bool( $value ) && ! is_array( $value ) && trim( (string) $value ) === '';
	}

	/**
	 * Get the keys for all of the input and files.
	 *
	 * @return array
	 */
	public function keys() {
		return array_merge( array_keys( $this->input() ), $this->files->keys() );
	}

	/**
	 * Get all of the input and files for the request.
	 *
	 * @param  array|mixed|null $keys
	 * @return array
	 */
	public function all( $keys = null ) {
		$input = $this->input();

		if ( ! $keys ) {
			return $input;
		}

		$results = [];

		foreach ( is_array( $keys ) ? $keys : func_get_args() as $key ) {
			Arr::set( $results, $key, Arr::get( $input, $key ) );
		}

		return $results;
	}

	/**
	 * Retrieve an input item from the request.
	 *
	 * @param  string|null $key
	 * @param  mixed       $default
	 * @return mixed
	 */
	public function input( $key = null, $default = null ) {
		return data_get(
			$this->get_input_source()->all() + $this->query->all(),
			$key,
			$default
		);
	}

	/**
	 * Retrieve input as a boolean value.
	 *
	 * Returns true when value is "1", "true", "on", and "yes". Otherwise, returns false.
	 *
	 * @param  string|null $key
	 * @param  bool        $default
	 * @return bool
	 */
	public function boolean( $key = null, $default = false ) {
		return filter_var( $this->input( $key, $default ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Get a subset containing the provided keys with values from the input data.
	 *
	 * @param  array|mixed $keys
	 * @return array
	 */
	public function only( $keys ) {
		$results = [];

		$input = $this->all();

		$placeholder = new stdClass();

		foreach ( is_array( $keys ) ? $keys : func_get_args() as $key ) {
			$value = data_get( $input, $key, $placeholder );

			if ( $value !== $placeholder ) {
				Arr::set( $results, $key, $value );
			}
		}

		return $results;
	}

	/**
	 * Get all of the input except for a specified array of items.
	 *
	 * @param  array|mixed $keys
	 * @return array
	 */
	public function except( $keys ) {
		$keys = is_array( $keys ) ? $keys : func_get_args();

		$results = $this->all();

		Arr::forget( $results, $keys );

		return $results;
	}

	/**
	 * Retrieve a query string item from the request.
	 *
	 * @param  string|null       $key
	 * @param  string|array|null $default
	 * @return string|array|null
	 */
	public function query( $key = null, $default = null ) {
		return $this->retrieve_item( 'query', $key, $default );
	}

	/**
	 * Retrieve a request payload item from the request.
	 *
	 * @param  string|null       $key
	 * @param  string|array|null $default
	 * @return string|array|null
	 */
	public function post( $key = null, $default = null ) {
		return $this->retrieve_item( 'request', $key, $default );
	}

	/**
	 * Determine if a cookie is set on the request.
	 *
	 * @param  string $key
	 * @return bool
	 */
	public function has_cookie( $key ) {
		return ! is_null( $this->cookie( $key ) );
	}

	/**
	 * Retrieve a cookie from the request.
	 *
	 * @param  string|null       $key
	 * @param  string|array|null $default
	 * @return string|array|null
	 */
	public function cookie( $key = null, $default = null ) {
		return $this->retrieve_item( 'cookies', $key, $default );
	}

	/**
	 * Retrieve a parameter item from a given source.
	 *
	 * @param  string            $source
	 * @param  string            $key
	 * @param  string|array|null $default
	 * @return string|array|null
	 */
	protected function retrieve_item( $source, $key, $default ) {
		if ( empty( $key ) ) {
			return $this->$source->all();
		}

		return $this->$source->get( $key, $default );
	}

	/**
	 * Get an array of all of the files on the request.
	 *
	 * @return array
	 */
	public function all_files() {
		$files = $this->files->all();

		if ( ! isset( $this->converted_files ) ) {
			$this->converted_files = $this->convert_uploaded_files( $files );
		}

		return $this->converted_files;
	}

	/**
	 * Convert the given array of Symfony Uploaded_Files to custom Mantle Uploaded_Files.
	 *
	 * @param  array $files
	 * @return array
	 */
	protected function convert_uploaded_files( array $files ) {
		return array_map(
			function ( $file ) {
				if ( is_null( $file ) || ( is_array( $file ) && empty( array_filter( $file ) ) ) ) {
					return $file;
				}

				return is_array( $file )
					? $this->convert_uploaded_files( $file )
					: Uploaded_File::createFromBase( $file );
			},
			$files
		);
	}

	/**
	 * Determine if the uploaded data contains a file.
	 *
	 * @param  string $key
	 */
	public function has_file( $key ): bool {
		$files = $this->file( $key );
		if ( ! is_array( $files ) ) {
			$files = [ $files ];
		}

		foreach ( $files as $file ) {
			if ( $this->is_valid_file( $file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check that the given file is a valid file instance.
	 *
	 * @param  mixed $file
	 */
	protected function is_valid_file( $file ): bool {
		return $file instanceof Uploaded_File && $file->getPath() !== '';
	}

	/**
	 * Retrieve a file from the request.
	 *
	 * @param  string|null $key
	 * @param  mixed       $default
	 * @return \Mantle\Http\Uploaded_File|\Mantle\Http\Uploaded_File[]|null
	 */
	public function file( $key = null, $default = null ) {
		return data_get( $this->all_files(), $key, $default );
	}
}
