<?php
/**
 * Response class file.
 *
 * @package Mantle
 */

namespace Mantle\Http;

use ArrayObject;
use InvalidArgumentException;
use JsonSerializable;
use Mantle\Contracts\Support\Arrayable;
use Mantle\Contracts\Support\Htmlable;
use Mantle\Contracts\Support\Jsonable;
use Mantle\Support\Traits\Macroable;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * HTTP Response
 */
class Response extends HttpFoundationResponse {
	use Macroable;

	/**
	 * The original content of the response.
	 *
	 * @var mixed
	 */
	public $original;

	/**
	 * Constructor.
	 *
	 * @param mixed                 $content Response content.
	 * @param int                   $status  Response status code.
	 * @param array<string, string> $headers Response headers.
	 *
	 * @throws InvalidArgumentException When the HTTP status code is not valid.
	 */
	public function __construct( $content = '', int $status = 200, array $headers = [] ) {
		$this->headers = new ResponseHeaderBag( $headers );
		$this->setContent( $content );
		$this->setStatusCode( $status );
		$this->setProtocolVersion( '1.0' );
	}

	/**
	 * Set the content on the response.
	 *
	 * @param  mixed $content
	 * @return $this
	 *
	 * @throws InvalidArgumentException When the HTTP status code is not valid.
	 */
	public function setContent( mixed $content ): static {
		$this->original = $content;

		// If the content is "JSONable" we will set the appropriate header and convert
		// the content to JSON. This is useful when returning something like models
		// from routes that will be automatically transformed to their JSON form.
		if ( $this->should_be_json( $content ) ) {
			$this->headers->set( 'Content-Type', 'application/json' );

			$content = $this->morph_to_json( $content );

			if ( false === $content ) {
				throw new InvalidArgumentException( json_last_error_msg() );
			}
		} elseif ( $content instanceof Htmlable ) {
			$content = $content->to_html();
		}

		parent::setContent( $content );

		return $this;
	}

	/**
	 * Determine if the given content should be turned into JSON.
	 *
	 * @param  Arrayable|Jsonable|ArrayObject|JsonSerializable|array|mixed $content
	 */
	protected function should_be_json( $content ): bool {
		return $content instanceof Arrayable ||
			$content instanceof Jsonable ||
			$content instanceof ArrayObject ||
			$content instanceof JsonSerializable ||
			is_array( $content );
	}

	/**
	 * Morph the given content into JSON.
	 *
	 * @param  Arrayable|Jsonable|ArrayObject|JsonSerializable|array|mixed $content
	 * @return string|false
	 */
	protected function morph_to_json( $content ) {
		if ( $content instanceof Jsonable ) {
			return $content->to_json();
		} elseif ( $content instanceof Arrayable ) {
			return json_encode( $content->to_array() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		}

		return json_encode( $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}
}
