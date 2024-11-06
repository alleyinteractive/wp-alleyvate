<?php
/**
 * Request class file
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

use Mantle\Support\Str;

use function Mantle\Support\Helpers\data_get;

/**
 * Request Record
 *
 * Used to store a request that was made via the WordPress HTTP API that can be
 * asserted against.
 */
class Request {
	/**
	 * Constructor
	 *
	 * @param array  $args Arguments of the request.
	 * @param string $url  URL of the request.
	 */
	public function __construct( protected array $args, protected string $url ) {
		// Format the headers to be lowercase.
		$this->args['headers'] = array_change_key_case( $this->args['headers'] ?? [] );
	}

	/**
	 * Retrieve the URL of the request.
	 */
	public function url(): string {
		return $this->url;
	}

	/**
	 * Retrieve the method of the request.
	 * The method is always uppercase.
	 */
	public function method(): string {
		return strtoupper( $this->args['method'] ?? '' );
	}

	/**
	 * Retrieve the enum method of the request.
	 */
	public function enum_method(): Http_Method {
		return Http_Method::from( $this->method() );
	}

	/**
	 * Check if the request has a set of headers.
	 *
	 * @param array $headers Headers to check for.
	 */
	public function has_headers( array $headers ): bool {
		foreach ( $headers as $key => $value ) {
			if ( ! $this->has_header( $key, $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine if the request has a given header.
	 *
	 * @param string $header Header to compare.
	 * @param mixed  $value Header value to compare, optional.
	 * @return boolean
	 */
	public function has_header( string $header, $value = null ) {
		$header = strtolower( $header );

		if ( is_null( $value ) ) {
			return isset( $this->args['headers'][ $header ] );
		}

		return isset( $this->args['headers'][ $header ] ) && $value === $this->args['headers'][ $header ];
	}

	/**
	 * Retrieve the value of a header.
	 *
	 * @param string $header Header to retrieve.
	 * @return mixed
	 */
	public function header( string $header ) {
		$header = strtolower( $header );

		return $this->args['headers'][ $header ] ?? null;
	}

	/**
	 * Retrieve the body of the request.
	 */
	public function body(): string {
		return $this->args['body'] ?? '';
	}

	/**
	 * Retrieve the JSON decoded body of the request.
	 *
	 * @return array|null
	 */
	public function json() {
		return json_decode( $this->body(), true );
	}

	/**
	 * Determine if the request is simple form data.
	 */
	public function is_form(): bool {
		return $this->has_header( 'Content-Type', 'application/x-www-form-urlencoded' );
	}

	/**
	 * Determine if the request is JSON.
	 */
	public function is_json(): bool {
		return $this->has_header( 'Content-Type' )
			&& Str::contains( $this->header( 'Content-Type' ), 'json' );
	}

	/**
	 * Retrieve a specific value from the request.
	 *
	 * @param string $key Key to retrieve.
	 * @return mixed
	 */
	public function get( string $key ) {
		return data_get( $this->args, $key );
	}

	/**
	 * Dump the request to the screen.
	 *
	 * @return static
	 */
	public function dump() {
		dump( $this->args, $this->url );
		return $this;
	}

	/**
	 * Dump the request to the screen and die.
	 */
	public function dd(): never {
		$this->dump();
		exit( 1 );
	}
}
