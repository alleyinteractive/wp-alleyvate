<?php
/**
 * Reads_Annotations trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use PHPUnit\Metadata\Annotation\Parser\DocBlock;
use PHPUnit\Metadata\Annotation\Parser\Registry;
use PHPUnit\Util\Test;
use ReflectionClass;

/**
 * Read annotations for testing that supports multiple versions of PHPUnit.
 *
 * @mixin \PHPUnit\Framework\TestCase
 */
trait Reads_Annotations {
	/**
	 * Read docblock annotations for the current test case and method.
	 */
	public function get_annotations_for_method(): array {
		// PHPUnit 9.4 and below method.
		if ( method_exists( $this, 'getAnnotations' ) ) {
			return $this->getAnnotations();
		}

		// Use the PHPUnit ^9.5 method if available.
		if ( method_exists( Test::class, 'parseTestMethodAnnotations' ) ) { // @phpstan-ignore-line
			return Test::parseTestMethodAnnotations(
				static::class,
				$this->getName(), // @phpstan-ignore-line
			);
		}

		// Use the PHPUnit 10.x method if available.
		if ( class_exists( Registry::class ) && class_exists( DocBlock::class ) ) {
			$registry = Registry::getInstance();

			return [
				'class'  => $registry->forClassName( static::class )->symbolAnnotations(),
				'method' => $registry->forMethod( static::class, $this->name() )->symbolAnnotations(),
			];
		}

		// Throw a warning if we can't read annotations.
		trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			'Unable to read annotations for test method. Please file an issue with https://github.com/alleyinteractive/mantle-framework',
			E_USER_WARNING
		);

		return [];
	}

	/**
	 * Read the attributes for the current test case and method.
	 *
	 * Supports PHPUnit 9.5+ and 10.x.
	 *
	 * @param class-string $name Filter the results to include only ReflectionAttribute instances for attributes matching this class name.
	 * @return array<\ReflectionAttribute>
	 */
	public function get_attributes_for_method( ?string $name = null ): array {
		$class = new ReflectionClass( $this );

		// Use either the PHPUnit 9.5+ method or the PHPUnit 10.x method to get the method.
		if ( method_exists( $this, 'getName' ) ) {
			$method = $class->getMethod( $this->getName( false ) );
		} elseif ( method_exists( $this, 'name' ) ) {
			$method = $class->getMethod( $this->name() );
		} elseif ( isset( $this->name ) ) {
			$method = $class->getMethod( $this->name );
		} else {
			trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				'Unable to read annotations for test method. Please file an issue with https://github.com/alleyinteractive/mantle-framework',
			);

			return [];
		}

		return [
			...$class->getAttributes( $name ),
			...$method->getAttributes( $name ),
		];
	}
}
