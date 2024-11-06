<?php
/**
 * HTML_Driver class file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Snapshots;

use DOMDocument;
use Spatie\Snapshots\Drivers\HtmlDriver;
use Spatie\Snapshots\Exceptions\CantBeSerialized;

/**
 * HTML Driver for Snapshot Testing
 *
 * Extends the base HtmlDriver with additional flags to ignore HTML errors.
 */
class HTML_Driver extends HtmlDriver {
	/**
	 * Serialize data to html
	 *
	 * @param mixed $data Data to serialize.
	 * @throws CantBeSerialized If data cannot be serialized.
	 */
	public function serialize( mixed $data ): string {
		if ( ! is_string( $data ) ) {
			throw new CantBeSerialized( 'Only strings can be serialized to html' );
		}

		if ( '' === $data ) {
			return "\n";
		}

		$document = new DOMDocument( '1.0' );

		$document->preserveWhiteSpace = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$document->formatOutput       = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		@$document->loadHTML( $data, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Generic.PHP.NoSilencedErrors.Forbidden

		$value = $document->saveHTML();

		// Normalize line endings for cross-platform tests.
		if ( PHP_OS_FAMILY === 'Windows' ) {
			return implode( "\n", explode( "\r\n", $value ) );
		}

		return $value;
	}
}
