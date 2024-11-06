<?php
/**
 * Autoloaded File to support Testing
 *
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Faker\Factory;
use Faker\Generator;
use Mantle\Container\Container;
use Mantle\Faker\Faker_Provider;

use function Mantle\Support\Helpers\tap;

require_once __DIR__ . '/preload.php';
require_once __DIR__ . '/mail/helpers.php';

/**
 * Retrieve an instance of the Installation Manager
 *
 * The manager can install the Mantle Testing Framework but will not by default.
 * Call {@see Installation_Manager::install()} to install or use the
 * {@see install()} helper.
 */
function manager(): Installation_Manager {
	return Installation_Manager::instance();
}

/**
 * Install the Mantle Testing Framework
 *
 * @param callable $callback Callback to invoke once the installation has begun.
 */
function install( callable $callback = null ): Installation_Manager {
	return tap(
		manager(),
		fn ( Installation_Manager $manager ) => $manager->before( $callback ),
	)->install();
}

/**
 * Create a new HTML_String instance.
 *
 * @param string $html The HTML string to test.
 */
function html_string( string $html ): Assertable_HTML_String {
	return new Assertable_HTML_String( $html );
}

/**
 * Create a new Mock HTTP Response
 *
 * @param string $body    Response body.
 * @param array  $headers Response headers.
 */
function mock_http_response( string $body = '', array $headers = [] ): Mock_Http_Response {
	return new Mock_Http_Response( $body, $headers );
}

/**
 * Create a new Mock HTTP Response Sequence
 */
function mock_http_sequence(): Mock_Http_Sequence {
	return new Mock_Http_Sequence();
}

/**
 * Create a new block factory instance.
 */
function block_factory(): Block_Factory {
	$container = Container::get_instance();

	// If the Generator is not bound to the container, bind it.
	if ( ! $container->bound( Generator::class ) ) {
		$container->singleton(
			Generator::class,
			fn () => tap(
				Factory::create(),
				fn ( Generator $generator ) => $generator->addProvider( new Faker_Provider( $generator ) ),
			),
		);
	}

	return $container->make( Block_Factory::class );
}
