<?php
/**
 * Event_Service_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Events;

use Mantle\Support\Service_Provider;

/**
 * Event Service Provider
 *
 * Registered the internal events dispatcher. The application's event service
 * provider extends {@see \Mantle\Framework\Providers\Event_Service_Provider}.
 */
class Event_Service_Provider extends Service_Provider {
	/**
	 * Register any application services.
	 */
	public function register(): void {
		$this->app->singleton_if(
			'events',
			fn ( $app ) => new Dispatcher( $app ),
		);
	}
}
