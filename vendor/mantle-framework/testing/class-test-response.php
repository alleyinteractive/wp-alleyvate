<?php // phpcs:disable WordPress.NamingConventions.ValidFunctionName
/**
 * This file contains the Test_Response class
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Exception;
use Mantle\Contracts\Application;
use Mantle\Http\Response;
use Mantle\Support\Traits\Macroable;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Faux "Response" class for unit testing.
 */
class Test_Response {
	use Concerns\Element_Assertions;
	use Concerns\Response_Snapshot_Testing;
	use Macroable;

	/**
	 * Application instance.
	 */
	protected Application $app;

	/**
	 * Response headers.
	 */
	public array $headers;

	/**
	 * Response content.
	 */
	protected string $content;

	/**
	 * Response status code.
	 */
	protected int $status_code;

	/**
	 * Assertable JSON string.
	 */
	protected Assertable_Json_String $decoded_json;

	/**
	 * Create a new test response instance.
	 *
	 * @param string|null $content HTTP response body.
	 * @param int         $status  HTTP response status code.
	 * @param array       $headers HTTP response headers.
	 * @param Test_Case   $test_case Test case instance.
	 */
	public function __construct(
		?string $content = '',
		int $status = 200,
		array $headers = [],
		public ?Test_Case $test_case = null,
	) {
		$this->set_content( $content )
			->set_status_code( $status )
			->set_headers( $headers );
	}

	/**
	 * Set the container instance.
	 *
	 * @param Application $app Application instance.
	 * @return static
	 */
	public function set_app( Application $app ) {
		$this->app = $app;

		return $this;
	}

	/**
	 * Create a response from a base response instance.
	 *
	 * @param Response $response Base response instance.
	 * @return static
	 */
	public static function from_base_response( Response $response ) {
		return new static( $response->getContent(), $response->getStatusCode(), $response->headers->all() );
	}

	/**
	 * Sets the response status code.
	 *
	 * @param int $code Status code.
	 * @return $this
	 */
	public function set_status_code( int $code ): object {
		$this->status_code = $code;

		return $this;
	}

	/**
	 * Retrieves the status code for the current web response.
	 */
	public function get_status_code(): int {
		return $this->status_code;
	}

	/**
	 * Sets the response content.
	 *
	 * @param string|null $content Response content.
	 * @return $this
	 */
	public function set_content( ?string $content ): object {
		$this->content = $content ?? '';

		return $this;
	}

	/**
	 * Gets the current response content.
	 *
	 * @return string|false
	 */
	public function get_content() {
		return $this->content;
	}

	/**
	 * Sets the response headers.
	 *
	 * @param array $headers Headers to set, as key => value pairs.
	 * @return $this
	 */
	public function set_headers( array $headers ): object {
		$this->headers = array_change_key_case( $headers, CASE_LOWER );

		return $this;
	}

	/**
	 * Gets the current response headers.
	 *
	 * @return array
	 */
	public function get_headers() {
		return $this->headers;
	}

	/**
	 * Gets the current response headers.
	 *
	 * @param string      $key     Header to return.
	 * @param string|null $default If the header is not set, default to return.
	 */
	public function get_header( string $key, string $default = null ): ?string {
		// Enforce a lowercase header name.
		$key = strtolower( $key );

		// If the header is set and not null, return the string value.
		if ( isset( $this->headers[ $key ] ) ) {
			// Account for multiple headers with the same key.
			return is_array( $this->headers[ $key ] )
				? (string) ( $this->headers[ $key ][0] ?? '' )
				: (string) $this->headers[ $key ];
		}

		// If the header is set and null, return that. Otherwise, the default.
		return array_key_exists( $key, $this->headers )
			? $this->headers[ $key ]
			: $default;
	}

	/**
	 * Assert that the response has a successful status code.
	 */
	public function assertSuccessful(): static {
		$actual = $this->get_status_code();

		PHPUnit::assertTrue(
			$actual >= 200 && $actual < 300,
			'Response status code [' . $actual . '] is not a successful status code.'
		);

		return $this;
	}

	/**
	 * Assert that the response has a 200 status code.
	 */
	public function assertOk(): static {
		return $this->assertStatus( 200 );
	}

	/**
	 * Assert that the response has the given status code.
	 *
	 * @param int $status Status code to assert.
	 */
	public function assertStatus( $status ): static {
		$actual = $this->get_status_code();

		PHPUnit::assertSame(
			$actual,
			$status,
			"Expected status code {$status} but received {$actual}."
		);

		return $this;
	}

	/**
	 * Assert that the response has a 201 status code.
	 */
	public function assertCreated(): static {
		return $this->assertStatus( 201 );
	}

	/**
	 * Assert that the response has the given status code and no content.
	 *
	 * @param int $status Status code to assert. Defaults to 204.
	 */
	public function assertNoContent( $status = 204 ): static {
		$this->assertStatus( $status );

		PHPUnit::assertEmpty( $this->get_content(), 'Response content is not empty.' );

		return $this;
	}

	/**
	 * Assert that the response has a not found status code.
	 */
	public function assertNotFound(): static {
		return $this->assertStatus( 404 );
	}

	/**
	 * Assert that the response has a forbidden status code.
	 */
	public function assertForbidden(): static {
		return $this->assertStatus( 403 );
	}

	/**
	 * Assert that the response has an unauthorized status code.
	 */
	public function assertUnauthorized(): static {
		return $this->assertStatus( 401 );
	}

	/**
	 * Assert that the response has a client error status code.
	 */
	public function assertClientError(): static {
		$status = $this->get_status_code();

		PHPUnit::assertTrue(
			$status >= 400 && $status < 500,
			"Response status code [{$status}] is not a client error status code.",
		);

		return $this;
	}

	/**
	 * Assert that the response has a server error status code.
	 */
	public function assertServerError(): static {
		$status = $this->get_status_code();

		PHPUnit::assertTrue(
			$status >= 500 && $status < 600,
			"Response status code [{$status}] is not a server error status code.",
		);

		return $this;
	}

	/**
	 * Assert whether the response is redirecting to a given URI.
	 *
	 * @param string|null $uri URI to assert redirection to.
	 */
	public function assertRedirect( ?string $uri = null ): static {
		PHPUnit::assertTrue(
			$this->is_redirect(),
			'Response status code [' . $this->get_status_code() . '] is not a redirect status code.'
		);

		if ( $uri ) {
			$this->assertLocation( $uri );
		}

		return $this;
	}

	/**
	 * Is the response a redirect of some form?
	 *
	 * @param string|null $location Location to check with the redirect.
	 */
	public function is_redirect( string $location = null ): bool {
		return in_array( $this->get_status_code(), [ 201, 301, 302, 303, 307, 308 ], true )
			&& ( null === $location ?: $location === $this->get_header( 'Location' ) ); // phpcs:ignore WordPress.PHP.DisallowShortTernary.Found
	}

	/**
	 * Assert that the current location header matches the given URI.
	 *
	 * @param string $uri URI to assert that the location header is set to.
	 * @return static
	 */
	public function assertLocation( $uri ) {
		PHPUnit::assertEquals(
			$this->app['url']->to( $uri ),
			$this->app['url']->to( $this->get_header( 'location', '' ) ),
		);

		return $this;
	}

	/**
	 * Asserts that the response contains the given header and equals the
	 * optional value.
	 *
	 * @param string $header_name Header name (key) to assert.
	 * @param mixed  $value       Header value to assert.
	 * @return static
	 */
	public function assertHeader( $header_name, $value = null ) {
		// Enforce a lowercase header name.
		$header_name = strtolower( $header_name );

		PHPUnit::assertArrayHasKey(
			$header_name,
			$this->headers,
			"Header [{$header_name}] not present on response."
		);

		$actual = $this->get_header( $header_name );

		if ( ! is_null( $value ) ) {
			PHPUnit::assertEquals(
				$value,
				$this->get_header( $header_name ),
				"Header [{$header_name}] was found, but value [{$actual}] does not match [{$value}]."
			);
		}

		return $this;
	}

	/**
	 * Asserts that the response does not contains the given header and optional
	 * value.
	 *
	 * @param string $header_name Header name (key) to check.
	 * @param mixed  $value       Header value to check, optional.
	 * @return $this
	 */
	public function assertHeaderMissing( string $header_name, mixed $value = null ) {
		// Enforce a lowercase header name.
		$header_name = strtolower( $header_name );

		// Compare the header value if one was provided.
		if ( ! is_null( $value ) ) {
			PHPUnit::assertNotEquals(
				$value,
				$this->get_header( $header_name ),
				"Unexpected header [{$header_name}] was found with value [{$value}]."
			);
		} else {
			PHPUnit::assertArrayNotHasKey(
				$header_name,
				$this->headers,
				"Unexpected header [{$header_name}] is present on response."
			);
		}

		return $this;
	}

	/**
	 * Asset that the contents matches an expected value.
	 *
	 * @param mixed $value Contents to compare.
	 * @return $this
	 */
	public function assertContent( mixed $value ): static {
		PHPUnit::assertEquals( $value, $this->get_content() );
		return $this;
	}

	/**
	 * Assert that the given string is contained within the response.
	 *
	 * @param string $value String to search for.
	 * @return $this
	 */
	public function assertSee( $value ) {
		PHPUnit::assertStringContainsString( (string) $value, $this->get_content() );

		return $this;
	}

	/**
	 * Look for $values in $content in the specified order.
	 *
	 * @throws \Exception On failure.
	 *
	 * @param array  $values  Strings in which to look for in order.
	 * @param string $content Content in which to look.
	 * @return bool True on success.
	 */
	public function see_in_order( array $values, string $content ): bool {
		$position = 0;

		foreach ( $values as $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			$value_position = mb_strpos( $content, (string) $value, $position );

			if ( false === $value_position || $value_position < $position ) {
				throw new Exception(
					sprintf(
						'Failed asserting that \'%s\' contains "%s" in specified order.',
						$content,
						$value
					)
				);
			}

			$position = $value_position + mb_strlen( (string) $value );
		}

		return true;
	}

	/**
	 * Assert that the given strings are contained in order within the response.
	 *
	 * @param array $values Values to check.
	 * @return $this
	 */
	public function assertSeeInOrder( array $values ) {
		try {
			PHPUnit::assertTrue( $this->see_in_order( $values, $this->get_content() ) );
		} catch ( Exception $exception ) {
			PHPUnit::fail( $exception->getMessage() );
		}

		return $this;
	}

	/**
	 * Assert that the given string is contained within the response text.
	 *
	 * @param string $value Value to check.
	 * @return $this
	 */
	public function assertSeeText( $value ) {
		PHPUnit::assertStringContainsString( (string) $value, wp_strip_all_tags( $this->get_content() ) );

		return $this;
	}

	/**
	 * Assert that the given strings are contained in order within the response
	 * text.
	 *
	 * @param array $values Values to check.
	 * @return $this
	 */
	public function assertSeeTextInOrder( array $values ) {
		try {
			PHPUnit::assertTrue(
				$this->see_in_order( $values, wp_strip_all_tags( $this->get_content() ) )
			);
		} catch ( Exception $exception ) {
			PHPUnit::fail( $exception->getMessage() );
		}

		return $this;
	}

	/**
	 * Assert that the given string is not contained within the response.
	 *
	 * @param string $value Value to check.
	 * @return $this
	 */
	public function assertDontSee( $value ) {
		PHPUnit::assertStringNotContainsString( (string) $value, $this->get_content() );

		return $this;
	}

	/**
	 * Assert that the given string is not contained within the response text.
	 *
	 * @param string $value Value to check.
	 * @return $this
	 */
	public function assertDontSeeText( $value ) {
		PHPUnit::assertStringNotContainsString( (string) $value, wp_strip_all_tags( $this->get_content() ) );

		return $this;
	}

	/**
	 * Checks each of the WP_Query is_* functions/properties against expected
	 * boolean value.
	 *
	 * @see Test_Case::assertQueryTrue()
	 *
	 * @param string ...$prop Any number of WP_Query properties that are expected
	 *                        to be true for the current request.
	 */
	public function assertQueryTrue( ...$prop ): static {
		Test_Case::assertQueryTrue( ...$prop );

		return $this;
	}

	/**
	 * Assert that a given ID matches the global queried object ID.
	 *
	 * @param int $id Expected ID.
	 * @return $this
	 */
	public function assertQueriedObjectId( int $id ): static {
		Test_Case::assertQueriedObjectId( $id );

		return $this;
	}

	/**
	 * Assert that a given ID does not the global queried object ID.
	 *
	 * @param int $id Expected ID.
	 * @return $this
	 */
	public function assertNotQueriedObjectId( int $id ): static {
		Test_Case::assertNotQueriedObjectId( $id );

		return $this;
	}

	/**
	 * Assert that a given object is equivalent to the global queried object.
	 *
	 * @param object $object Expected object.
	 * @return $this
	 */
	public function assertQueriedObject( mixed $object ): static {
		Test_Case::assertQueriedObject( $object );

		return $this;
	}
	/**
	 * Assert that a given object is not equivalent to the global queried object.
	 *
	 * @param object $object Expected object.
	 * @return $this
	 */
	public function assertNotQueriedObject( mixed $object ): static {
		Test_Case::assertNotQueriedObject( $object );

		return $this;
	}

	/**
	 * Assert that the queried object is null.
	 *
	 * @return $this
	 */
	public function assertQueriedObjectNull(): static {
		Test_Case::assertQueriedObjectNull();

		return $this;
	}

	/**
	 * Assert if the response is a JSON response.
	 *
	 * @return $this
	 */
	public function assertIsJson(): static {
		$content_type = $this->get_header( 'Content-Type' );

		if ( empty( $content_type ) ) {
			PHPUnit::fail( 'Response is not JSON.' );
		}

		// Check that the content-type header contains 'application/json'.
		PHPUnit::assertStringContainsString( 'application/json', $content_type );

		// Decode the content and see if it's valid JSON. If it isn't, the test will
		// fail.
		$this->decoded_json();

		return $this;
	}

	/**
	 * Assert if the response is not a JSON response.
	 *
	 * @return $this
	 */
	public function assertIsNotJson(): static {
		$content_type = $this->get_header( 'Content-Type' );

		PHPUnit::assertStringNotContainsString( 'application/json', $content_type );

		// Bail early if the content type is not JSON.
		if ( empty( $content_type ) ) {
			return $this;
		}

		if ( isset( $this->decoded_json ) ) {
			PHPUnit::fail( 'Response is JSON.' );
		}

		if ( ! empty( $this->content ) ) {
			// Attempt to parse the content and see if it's valid JSON.
			$decoded = json_decode( $this->content, true );

			if ( null !== $decoded ) {
				PHPUnit::fail( 'Response is JSON.' );
			}
		}

		return $this;
	}

	/**
	 * Assert that the expected value and type exists at the given path in the response.
	 *
	 * @param  string $path
	 * @param  mixed  $expect
	 * @return $this
	 */
	public function assertJsonPath( $path, $expect ) {
		$this->decoded_json()->assertPath( $path, $expect );

		return $this;
	}

	/**
	 * Assert that a specific path exists in the response.
	 *
	 * @param string $path Path to check.
	 */
	public function assertJsonPathExists( string $path ) {
		$this->decoded_json()->assertPathExists( $path );

		return $this;
	}

	/**
	 * Assert that a specific path does not exist in the response.
	 *
	 * @param string $path Path to check.
	 */
	public function assertJsonPathMissing( string $path ) {
		$this->decoded_json()->assertPathMissing( $path );

		return $this;
	}

	/**
	 * Assert that the response has the exact given JSON.
	 *
	 * @param  array $data
	 * @return $this
	 */
	public function assertExactJson( array $data ) {
		$this->decoded_json()->assertExact( $data );

		return $this;
	}

	/**
	 * Assert that the response contains the given JSON fragment.
	 *
	 * @param  array $data Data to compare.
	 * @return $this
	 */
	public function assertJsonFragment( array $data ) {
		$this->decoded_json()->assertFragment( $data );

		return $this;
	}

	/**
	 * Assert that the response does not contain the given JSON fragment.
	 *
	 * @param  array $data Data to compare.
	 * @param  bool  $exact Flag for exact match, defaults to false.
	 * @return $this
	 */
	public function assertJsonMissing( array $data, $exact = false ) {
		$this->decoded_json()->assertMissing( $data, $exact );

		return $this;
	}

	/**
	 * Assert that the response does not contain the exact JSON fragment.
	 *
	 * @param  array $data
	 * @return $this
	 */
	public function assertJsonMissingExact( array $data ) {
		$this->decoded_json()->assertMissingExact( $data );

		return $this;
	}

	/**
	 * Assert that the response JSON has the expected count of items at the given key.
	 *
	 * @param  int         $count
	 * @param  string|null $key
	 * @return $this
	 */
	public function assertJsonCount( int $count, $key = null ) {
		$this->decoded_json()->assertCount( $count, $key );

		return $this;
	}

	/**
	 * Assert that the response has the similar JSON as given.
	 *
	 * @param  array $data
	 * @return $this
	 */
	public function assertJsonSimilar( array $data ) {
		$this->decoded_json()->assertSimilar( $data );

		return $this;
	}

	/**
	 * Assert that the response has a given JSON structure.
	 *
	 * @param  array|null $structure Structure to check.
	 * @return $this
	 */
	public function assertJsonStructure( array $structure = null ) {
		$this->decoded_json()->assertStructure( $structure );

		return $this;
	}

	/**
	 * Validate and assert against the decoded JSON content.
	 */
	public function decoded_json(): Assertable_Json_String {
		if ( ! isset( $this->decoded_json ) ) {
			$this->decoded_json = new Assertable_Json_String( $this->get_content() );
		}

		return $this->decoded_json;
	}

	/**
	 * Return the decoded response JSON.
	 *
	 * @param string|null $key Key to retrieve, optional.
	 * @return mixed
	 */
	public function json( ?string $key = null ) {
		return $this->decoded_json()->json( $key );
	}

	/**
	 * Dump the contents of the response to the screen.
	 */
	public function dump(): static {
		$content = $this->get_content();

		// If the content is not JSON, dump it as is.
		if ( 'application/json' !== $this->get_header( 'Content-Type' ) ) {
			dump( $content );

			return $this;
		}

		$json = json_decode( $content );

		if ( json_last_error() === JSON_ERROR_NONE ) {
			$content = $json;
		}

		dump( $content );

		return $this;
	}

	/**
	 * Dump the headers of the response to the screen.
	 */
	public function dump_headers(): static {
		dump( $this->headers );

		return $this;
	}

	/**
	 * Camel-case alias to dump_headers().
	 */
	public function dumpHeaders(): static {
		return $this->dump_headers();
	}

	/**
	 * Dump the JSON, optionally by path, to the screen.
	 *
	 * @param string|null $path
	 */
	public function dump_json( ?string $path = null ): static {
		dump( $this->json( $path ) );

		return $this;
	}

	/**
	 * Camel-case alias to dump_json().
	 *
	 * @param string|null $path
	 */
	public function dumpJson( ?string $path = null ): static {
		return $this->dump_json( $path );
	}

	/**
	 * Dump the content from the response and end the script.
	 */
	public function dd(): void {
		$this->dump();

		exit( 1 );
	}

	/**
	 * Dump the headers from the response and end the script.
	 */
	public function dd_headers(): void {
		$this->dump_headers();

		exit( 1 );
	}

	/**
	 * Camel-case alias to dd_headers().
	 */
	public function ddHeaders(): void {
		$this->dd_headers();
	}

	/**
	 * Dump the JSON from the response and end the script.
	 *
	 * @param string|null $path
	 */
	public function dd_json( ?string $path = null ): void {
		$this->dump_json( $path );

		exit( 1 );
	}

	/**
	 * Camel-case alias to dd_json().
	 *
	 * @param string|null $path
	 */
	public function ddJson( ?string $path = null ): void {
		$this->dd_json( $path );
	}
}
