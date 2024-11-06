<?php
/**
 * Snapshot_Testing trait file
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use DOMDocument;
use Mantle\Support\Arr;
use Mantle\Support\Str;
use Mantle\Testing\Snapshots\HTML_Driver;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\data_get;

/**
 * Response Snapshot Testing
 *
 * @link https://github.com/spatie/phpunit-snapshot-assertions
 *
 * @mixin \Mantle\Testing\Test_Response
 */
trait Response_Snapshot_Testing {
	/**
	 * Assert that the response matches a stored snapshot comparing only the content.
	 *
	 * Alias to `assertMatchesSnapshotContent()`.
	 *
	 * @param mixed ...$args Optional. Additional arguments to pass to the snapshot assertion.
	 */
	public function assertMatchesSnapshot( ...$args ): static {
		return $this->assertMatchesSnapshotContent( ...$args );
	}

	/**
	 * Assert that the response's content matches a stored snapshot.
	 *
	 * Checks the response content-type to use the proper driver to make the
	 * assertion against.
	 *
	 * @param mixed ...$args Optional. Additional arguments to pass to the snapshot assertion.
	 */
	public function assertMatchesSnapshotContent( ...$args ): static {
		if ( $this->test_case ) {
			$content_type = $this->get_header( 'content-type' );

			if ( Str::contains( $content_type, 'application/json', true ) ) {
				return $this->assertMatchesSnapshotJson( ...$args );
			} elseif ( Str::contains( $content_type, 'text/html', true ) ) {
				return $this->assertMatchesSnapshotHtml( ...$args );
			} else {
				$this->test_case->assertMatchesSnapshot( $this->get_content() );
			}
		}

		return $this;
	}

	/**
	 * Assert that the response's HTML content matches a stored snapshot.
	 *
	 * @param array<string>|string|null $selectors Optional. The XPath selectors to include in the snapshot, or null to include the entire content. Defaults to the entire content.
	 */
	public function assertMatchesSnapshotHtml( array|string $selectors = null ): static {
		if ( ! $this->test_case ) {
			return $this;
		}

		if ( empty( $selectors ) ) {
			$this->test_case->assertMatchesSnapshot( $this->get_content(), new HTML_Driver() );

			return $this;
		}

		if ( ! is_array( $selectors ) ) {
			$selectors = [ $selectors ];
		}

		// Extract from the content by the XPath selectors.
		libxml_use_internal_errors( true );

		$document = new DOMDocument( '1.0' );

		// Mirror the internal HtmlDriver of the snapshot assertions package.
		$document->preserveWhiteSpace = false;
		$document->formatOutput       = true;

		// To ignore HTML5 errors.
		@$document->loadHTML( $this->get_content(), LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Generic.PHP.NoSilencedErrors.Forbidden

		$nodes = ( new \DOMXPath( $document ) )->query( implode( '|', $selectors ) );

		if ( 0 === count( $nodes ) ) {
			$this->test_case->fail( 'No nodes found for the given XPath selector(s): ' . print_r( $selectors, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}

		$results = [];

		foreach ( $nodes as $node ) {
			$results[] = $document->saveHTML( $node );
		}

		$this->test_case->assertMatchesSnapshot( implode( "\n", $results ), new HTML_Driver() );

		return $this;
	}

	/**
	 * Assert that the response's JSON content matches a stored snapshot.
	 *
	 * @param array<string>|string|null $keys Optional. The keys to include in the snapshot.
	 */
	public function assertMatchesSnapshotJson( array|string|null $keys = null ): static {
		if ( $this->test_case ) {
			$content = $this->decoded_json()->get_decoded();

			// If keys are provided, only include those keys in the snapshot.
			if ( $keys ) {
				$content = collect( Arr::wrap( $keys ) )
					->unique()
					->flip()
					->map(
						fn ( $value, string $key ) => data_get( $content, $key, [] ),
					)
					->to_array();
			}

			$this->test_case->assertMatchesJsonSnapshot( $content );
		}

		return $this;
	}

	/**
	 * Assert that the response matches the stored snapshot, comparing status
	 * code, headers, and content.
	 *
	 * **Note:** asserting against the headers of a response can lead to leaky tests
	 * that break not too long after they are written. `assertMatchesSnapshotContent()`
	 * is a better alternative.
	 */
	public function assertMatchesSnapshotWithStatusAndHeaders(): static {
		return $this
			->assertStatusAndHeadersMatchSnapshot()
			->assertMatchesSnapshotContent();
	}

	/**
	 * Assert that the response's status code and headers match a stored snapshot.
	 */
	public function assertStatusAndHeadersMatchSnapshot(): static {
		if ( $this->test_case ) {
			$this->test_case->assertMatchesSnapshot(
				[
					$this->get_status_code(),
					$this->get_headers(),
				]
			);
		}

		return $this;
	}
}
