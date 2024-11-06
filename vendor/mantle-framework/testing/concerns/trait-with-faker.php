<?php
/**
 * This file contains the With_Faker trait
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Faker\Generator;
use Faker\Factory;
use Mantle\Faker\Faker_Provider;

/**
 * This trait sets up a faker instance for use in tests.
 */
trait With_Faker {
	/**
	 * Faker instance.
	 */
	protected Generator $faker;

	/**
	 * Setup the Faker instance.
	 */
	public function with_faker_set_up(): void {
		$this->faker = $this->make_faker();

		$this->faker->unique( true );
	}

	/**
	 * Create a faker instance.
	 */
	protected function make_faker(): Generator {
		$locale = isset( $this->app['config'] )
			? $this->app['config']->get( 'app.faker_locale', Factory::DEFAULT_LOCALE )
			: Factory::DEFAULT_LOCALE;

		if ( isset( $this->app ) && $this->app->bound( Generator::class ) ) {
			return $this->app->make( Generator::class, [ 'locale' => $locale ] );
		}

		$generator = Factory::create( $locale );

		$generator->addProvider( new Faker_Provider( $generator ) );

		return $generator;
	}
}
