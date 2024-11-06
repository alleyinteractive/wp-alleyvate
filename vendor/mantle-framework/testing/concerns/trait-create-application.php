<?php
/**
 * Create_Application trait file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Application\Application;
use Mantle\Config\Repository;
use Mantle\Contracts\Exceptions\Handler as Handler_Contract;
use Mantle\Framework\Bootloader;
use Mantle\Framework\Exceptions\Handler;
use Mantle\Support\Collection;

/**
 * Concern for creating the application instance.
 */
trait Create_Application {
	/**
	 * Creates the application.
	 *
	 * @return Application
	 */
	public function create_application(): \Mantle\Contracts\Application {
		Bootloader::create( $app = new Application() )
			->with_config(
				[
					'view' => [
						'compiled' => sys_get_temp_dir(),
					],
					...$this->override_application_config( $app ),
				]
			);

		$app = new Application();

		$this->resolve_application_core( $app );
		$this->resolve_application_bindings( $app );

		return $app;
	}

	/**
	 * Override application bindings, to be overridden by the child unit test.
	 *
	 * @param Application $app Application instance.
	 * @return array
	 */
	protected function override_application_bindings( $app ) {
		return [];
	}

	/**
	 * Resolve application bindings.
	 *
	 * @param Application $app Application instance.
	 */
	final protected function resolve_application_bindings( $app ): void {
		$app->singleton( Handler_Contract::class, Handler::class );

		foreach ( $this->override_application_bindings( $app ) as $original => $replacement ) {
			$app->bind( $original, $replacement );
		}
	}

	/**
	 * Configuration for the test.
	 *
	 * @param Application $app Application instance.
	 */
	protected function override_application_config( $app ): array {
		return [];
	}

	/**
	 * Resolve application core configuration.
	 *
	 * @param Application $app Application instance.
	 */
	protected function resolve_application_core( $app ) {
		$app->make( \Mantle\Framework\Bootstrap\Load_Configuration::class )->bootstrap( $app );
		$app->make( \Mantle\Framework\Bootstrap\Register_Aliases::class )->bootstrap( $app );
		$app->make( \Mantle\Framework\Bootstrap\Register_Providers::class )->bootstrap( $app );
		$app->make( \Mantle\Framework\Bootstrap\Boot_Providers::class )->bootstrap( $app );
	}

	/**
	 * Get application providers.
	 *
	 * @param Application $app Application instance.
	 * @return array
	 */
	protected function get_application_providers( $app ) {
		return $app['config']['app.providers'];
	}

	/**
	 * Override application aliases.
	 *
	 * @param Application $app Application instance.
	 * @return array
	 */
	protected function override_application_providers( $app ) {
		return [];
	}

	/**
	 * Resolve application aliases.
	 *
	 * @param Application $app Application instance.
	 */
	final protected function resolve_application_providers( $app ): array {
		$providers = new Collection( $this->get_application_providers( $app ) );
		$overrides = $this->override_application_providers( $app );

		if ( ! empty( $overrides ) ) {
			$providers->transform(
				static fn ( $provider ) => $overrides[ $provider ] ?? $provider
			);
		}

		return $providers->all();
	}
}
