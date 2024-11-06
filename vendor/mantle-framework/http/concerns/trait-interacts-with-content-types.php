<?php
/**
 * Interacts_With_Content_Types trait file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Concerns;

use Mantle\Support\Str;

/**
 * Interacts with Content Types Concern
 *
 * Provides information about the current request to the request object.
 */
trait Interacts_With_Content_Types {

	/**
	 * Determine if the given content types match.
	 *
	 * @param  string $actual
	 * @param  string $type
	 */
	public static function matches_type( $actual, $type ): bool {
		if ( $actual === $type ) {
			return true;
		}

		$split = explode( '/', $actual );

		return isset( $split[1] ) && preg_match( '#' . preg_quote( $split[0], '#' ) . '/.+\+' . preg_quote( $split[1], '#' ) . '#', $type );
	}

	/**
	 * Determine if the request is sending JSON.
	 *
	 * @return bool
	 */
	public function is_json() {
		return Str::contains( $this->header( 'CONTENT_TYPE' ), [ '/json', '+json' ] );
	}

	/**
	 * Determine if the current request probably expects a JSON response.
	 */
	public function expects_json(): bool {
		if ( $this->ajax() && ! $this->pjax() && $this->accepts_any_content_type() ) {
			return true;
		}

		if ( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) {
			return true;
		}

		return (bool) $this->wants_json();
	}

	/**
	 * Determine if the current request is asking for JSON.
	 */
	public function wants_json(): bool {
		$acceptable = $this->getAcceptableContentTypes();

		return isset( $acceptable[0] ) && Str::contains( $acceptable[0], [ '/json', '+json' ] );
	}

	/**
	 * Determines whether the current requests accepts a given content type.
	 *
	 * @param  string|array $content_types
	 */
	public function accepts( $content_types ): bool {
		$accepts = $this->getAcceptableContentTypes();

		if ( count( $accepts ) === 0 ) {
			return true;
		}

		$types = (array) $content_types;

		foreach ( $accepts as $accept ) {
			if ( '*/*' === $accept || '*' === $accept ) {
				return true;
			}

			foreach ( $types as $type ) {
				if ( $this->matches_type( $accept, $type ) || strtok( $type, '/' ) . '/*' === $accept ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Return the most suitable content type from the given array based on content negotiation.
	 *
	 * @param  string|array $content_types
	 * @return string|null
	 */
	public function prefers( $content_types ) {
		$accepts = $this->getAcceptableContentTypes();

		$content_types = (array) $content_types;

		foreach ( $accepts as $accept ) {
			if ( in_array( $accept, [ '*/*', '*' ], true ) ) {
				return $content_types[0];
			}

			foreach ( $content_types as $content_type ) {
				$type = $content_type;

				$mime_type = $this->getMimeType( $content_type );
				if ( ! is_null( $mime_type ) ) {
					$type = $mime_type;
				}

				if ( $this->matches_type( $type, $accept ) || strtok( $type, '/' ) . '/*' === $accept ) {
					return $content_type;
				}
			}
		}

		return null;
	}

	/**
	 * Determine if the current request accepts any content type.
	 */
	public function accepts_any_content_type(): bool {
		$acceptable = $this->getAcceptableContentTypes();

		return count( $acceptable ) === 0 || (
			isset( $acceptable[0] ) && ( '*/*' === $acceptable[0] || '*' === $acceptable[0] )
		);
	}

	/**
	 * Determines whether a request accepts JSON.
	 *
	 * @return bool
	 */
	public function accepts_json() {
		return $this->accepts( 'application/json' );
	}

	/**
	 * Determines whether a request accepts HTML.
	 *
	 * @return bool
	 */
	public function accepts_html() {
		return $this->accepts( 'text/html' );
	}

	/**
	 * Get the data format expected in the response.
	 *
	 * @param  string $default
	 * @return string
	 */
	public function format( $default = 'html' ) {
		foreach ( $this->getAcceptableContentTypes() as $type ) {
			$format = $this->getFormat( $type );
			if ( $format ) {
				return $format;
			}
		}

		return $default;
	}
}
