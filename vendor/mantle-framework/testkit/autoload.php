<?php
/**
 * Testkit autoload file
 *
 * @package Mantle
 */

use NunoMaduro\Collision\Adapters\Phpunit\Subscribers\EnsurePrinterIsRegisteredSubscriber;
use PHPUnit\Event\Facade as PHPUnitFacade;
use PHPUnit\Runner\Version;

/**
 * Register the collision printer for PHPUnit 10.
 *
 * Due to a change in the collision package, the printer must be registered
 * manually unless the test suite is manually invoked by the code base (which
 * most projects do not use).
 *
 * The printer code is largely private and not intended to be used by external
 * code, so we need to carefully check for the existence of the classes and
 * methods we need to use.
 */
if (
	class_exists( Version::class )
	&& version_compare( Version::id(), '10.0.0', '>=' )
	&& empty( getenv( 'COLLISION_DISABLE' ) ) // A kill switch for disabling the printer.
	&& class_exists( PHPUnitFacade::class )
	&& class_exists( EnsurePrinterIsRegisteredSubscriber::class )
	&& method_exists( PHPUnitFacade::class, 'registerSubscriber' )
) {
	PHPUnitFacade::instance()->registerSubscriber( new EnsurePrinterIsRegisteredSubscriber() );
}
