<?php
/**
 * Create_Application trait file
 *
 * @package Mantle
 */

namespace Mantle\Testkit\Concerns;

use Mantle\Config\Repository;
use Mantle\Contracts\Exceptions\Handler as Handler_Contract;
use Mantle\Http\Request;
use Mantle\Http\Routing\Url_Generator;
use Mantle\Support\Collection;
use Mantle\Testkit\Application;
use Mantle\Testkit\Exception_Handler;
use Symfony\Component\Routing\RouteCollection;

/**
 * Concern for creating the application instance for Mantle TestKit.
 *
 * This trait is used to create a semi-isolated instance of the Mantle
 * Application that doesn't have all the bells and whistles of a full Mantle
 * Application. Notably, the service providers are never registered or booted
 * here.
 *
 * One thing to remember is that this package is largely used in isolation. The
 * dependent package will not have the files within the "framework" package and
 * cannot be used here. In a future version, we should consider loading the
 * framework's base configuration (in the config directory) instead of stubbing
 * out our defaults here. We should also consider reusing the bootloader here
 * and moving that to a standalone package.
 */
trait Create_Application {
	/**
	 * Creates the application.
	 *
	 * @return Application
	 */
	public function create_application(): \Mantle\Contracts\Application {
		$app = new Application();

		$this->resolve_application_bindings( $app );
		$this->resolve_application_config( $app );

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
		$app->singleton( Handler_Contract::class, Exception_Handler::class );

		foreach ( $this->override_application_bindings( $app ) as $original => $replacement ) {
			$app->bind( $original, $replacement );
		}

		$app->singleton_if(
			'url',
			fn ( $app ) => new Url_Generator(
				$app->get_root_url(),
				new RouteCollection(),
				$app['request'] ?? new Request(),
			),
		);
	}

	/**
	 * Default configuration for the test.
	 */
	protected function get_application_config(): array {
		return [
			'app'        => [
				'debug'     => true,
				'providers' => [],
			],
			'queue'      => [
				'batch_size' => 100,
				'default'    => 'wordpress',
			],
			'logging'    => [
				'default'  => 'error_log',
				'channels' => [
					'error_log' => [
						'driver' => 'error_log',
					],
				],
			],
			'view'       => [
				'compiled' => sys_get_temp_dir(),
			],
			'filesystem' => [],
			'cache'      => [],
		];
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
	 * @todo Allow for overriding the configuration aliases and providers easily within the unit test.
	 */
	protected function resolve_application_config( $app ) {
		$config = new Repository(
			array_merge(
				$this->get_application_config(),
				$this->override_application_config( $app )
			)
		);

		$app->instance( 'config', $config );
		$app['config']['app.providers'] = $this->resolve_application_providers( $app );
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
