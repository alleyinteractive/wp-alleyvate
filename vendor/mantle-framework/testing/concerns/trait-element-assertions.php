<?php //phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
/**
 * Element_Assertions trait file
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.ParamNameNoMatch, Squiz.Commenting.FunctionComment.MissingParamTag
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Assorted Test_Cast assertions for checking for elements in a response.
 */
trait Element_Assertions {
	/**
	 * DOM Document Storage.
	 */
	protected DOMDocument $document;

	/**
	 * Retrieve the DOM Document for the response.
	 */
	protected function get_dom_document(): DOMDocument {
		if ( isset( $this->document ) ) {
			return $this->document;
		}

		libxml_use_internal_errors( true );

		$this->document = new DOMDocument();
		$this->document->loadHTML( $this->get_content() );

		return $this->document;
	}

	/**
	 * Convert a CSS selector to an XPath query.
	 *
	 * @param string $selector The selector to convert.
	 */
	protected function convert_query_selector( string $selector ): string {
		$converter = new CssSelectorConverter( true );

		return $converter->toXPath( $selector );
	}

	/**
	 * Assert that an element exists in the response.
	 *
	 * @param string $expression The XPath expression to execute.
	 * @param string $message Optional message to display on failure.
	 */
	public function assertElementExists( string $expression, string $message = null ): static {
		$nodes = ( new DOMXPath( $this->get_dom_document() ) )->query( $expression );

		PHPUnit::assertTrue(
			! $nodes ? false : $nodes->length > 0,
			$message ?? 'Element not found for expression: ' . $expression,
		);

		return $this;
	}

	/**
	 * Assert that an element exists by its ID.
	 *
	 * @param string $id The ID of the element to check.
	 */
	public function assertElementExistsById( string $id ): static {
		if ( str_starts_with( $id, '#' ) ) {
			$id = substr( $id, 1 );
		}

		return $this->assertElementExists( sprintf( '//*[@id="%s"]', $id ), "Element not found for ID: $id" );
	}

	/**
	 * Assert that an element exists by its class name.
	 *
	 * @param string $classname The classname of the element to check.
	 */
	public function assertElementExistsByClassName( string $classname ): static {
		if ( str_starts_with( $classname, '.' ) ) {
			$classname = substr( $classname, 1 );
		}

		return $this->assertElementExists(
			sprintf( '//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $classname ),
			"Element not found for class: $classname"
		);
	}

	/**
	 * Assert that an element is missing in the response.
	 *
	 * @param string $expression The XPath expression to execute.
	 * @param string $message    The message to display if the assertion fails.
	 */
	public function assertElementMissing( string $expression, string $message = null ): static {
		$nodes = ( new DOMXPath( $this->get_dom_document() ) )->query( $expression );

		PHPUnit::assertTrue(
			false === $nodes || 0 === $nodes->length,
			$message ?? "Element found for expression: $expression"
		);

		return $this;
	}

	/**
	 * Assert that an element is missing by its ID.
	 *
	 * @param string $id The ID of the element to check.
	 */
	public function assertElementMissingById( string $id ): static {
		if ( str_starts_with( $id, '#' ) ) {
			$id = substr( $id, 1 );
		}

		return $this->assertElementMissing( sprintf( '//*[@id="%s"]', $id ), "Element found for ID: $id" );
	}

	/**
	 * Assert that an element is missing by its class name.
	 *
	 * @param string $classname The classname of the element to check.
	 */
	public function assertElementMissingByClassName( string $classname ): static {
		if ( str_starts_with( $classname, '.' ) ) {
			$classname = substr( $classname, 1 );
		}

		return $this->assertElementMissing( sprintf( '//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $classname ) );
	}

	/**
	 * Assert that an element exists by tag name.
	 *
	 * @param string $type The type of element to check.
	 */
	public function assertElementExistsByTagName( string $type ): static {
		return $this->assertElementExists( sprintf( '//*[local-name()="%s"]', $type ), "Element not found for tag: $type" );
	}

	/**
	 * Assert that an element is missing by tag name.
	 *
	 * @param string $type The type of element to check.
	 */
	public function assertElementMissingByTagName( string $type ): static {
		return $this->assertElementMissing( sprintf( '//*[local-name()="%s"]', $type ), "Element found for tag: $type" );
	}

	/**
	 * Assert that an element exists by query selector.
	 *
	 * @param string $selector The selector to use.
	 */
	public function assertElementExistsByQuerySelector( string $selector ): static {
		return $this->assertElementExists( $this->convert_query_selector( $selector ), "Element not found for selector: $selector" );
	}

	/**
	 * Alias for assertElementExistsByQuerySelector.
	 *
	 * @param string $selector
	 */
	public function assertQuerySelectorExists( string $selector ): static {
		return $this->assertElementExistsByQuerySelector( $selector );
	}

	/**
	 * Assert that an element is missing by query selector.
	 *
	 * @param string $selector The selector to use.
	 */
	public function assertElementMissingByQuerySelector( string $selector ): static {
		return $this->assertElementMissing( $this->convert_query_selector( $selector ), "Element found for selector: $selector" );
	}

	/**
	 * Alias for assertElementMissingByQuerySelector.
	 *
	 * @param string $selector
	 */
	public function assertQuerySelectorMissing( string $selector ): static {
		return $this->assertElementMissingByQuerySelector( $selector );
	}

	/**
	 * Assert against the expected number of elements for an expression.
	 *
	 * @param string $expression The XPath expression to execute.
	 * @param int    $expected The expected number of elements.
	 */
	public function assertElementCount( string $expression, int $expected ): static {
		$nodes = ( new DOMXPath( $this->get_dom_document() ) )->query( $expression );

		PHPUnit::assertEquals( $expected, $nodes->length, 'Unexpected number of elements found.' );

		return $this;
	}

	/**
	 * Assert against the expected number of elements for a query selector.
	 *
	 * @param string $selector The selector to use.
	 * @param int    $expected The expected number of elements.
	 */
	public function assertQuerySelectorCount( string $selector, int $expected ): static {
		return $this->assertElementCount( $this->convert_query_selector( $selector ), $expected );
	}

	/**
	 * Assert an element exists by test ID.
	 *
	 * @param string $test_id The test ID to check.
	 */
	public function assertElementExistsByTestId( string $test_id ): static {
		return $this->assertQuerySelectorExists( "[data-testid=\"$test_id\"]" );
	}

	/**
	 * Assert an element is missing by test ID.
	 *
	 * @param string $test_id The test ID to check.
	 */
	public function assertElementMissingByTestId( string $test_id ): static {
		return $this->assertQuerySelectorMissing( "[data-testid=\"$test_id\"]" );
	}

	/**
	 * Assert that an element passes a custom assertion.
	 *
	 * The assertion will be called for each node found by the expression.
	 *
	 * @param string           $expression The XPath expression to execute.
	 * @param callable(DOMNode $node): bool $assertion The assertion to run.
	 * @param bool             $pass_any Pass if any of the nodes pass the assertion. Otherwise, all must pass.
	 */
	public function assertElement( string $expression, callable $assertion, bool $pass_any = false ): static {
		$nodes = ( new DOMXPath( $this->get_dom_document() ) )->query( $expression );

		if ( ! $nodes ) {
			PHPUnit::fail( 'No nodes found for expression: ' . $expression );
		}

		foreach ( $nodes as $node ) {
			if ( $assertion( $node ) ) {
				// If we're passing on any, we can return early.
				if ( $pass_any ) {
					return $this;
				}
			} elseif ( ! $pass_any ) {
				PHPUnit::fail( 'Assertion failed for node: ' . $node->nodeName ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
		}

		PHPUnit::assertTrue( true, 'All nodes passed assertion.' );

		return $this;
	}

	/**
	 * Assert that an element passes a custom assertion by query selector.
	 *
	 * The assertion will be called for each node found by the selector.
	 *
	 * @param string           $selector The selector to use.
	 * @param callable(DOMNode $node): bool $assertion The assertion to run.
	 * @param bool             $pass_any Pass if any of the nodes pass the assertion. Otherwise, all must pass.
	 */
	public function assertQuerySelector( string $selector, callable $assertion, bool $pass_any = false ): static {
		return $this->assertElement( $this->convert_query_selector( $selector ), $assertion, $pass_any );
	}
}
