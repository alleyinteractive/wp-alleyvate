<?php
/**
 * Setup_WordPress class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Routing\Middleware;

use Closure;
use Mantle\Contracts\Application;
use Mantle\Http\Request;
use Mantle\Query_Monitor\Query_Monitor_Service_Provider;

/**
 * Setup the WordPress environment for routing.
 *
 * Ensures that normal WordPress hooks such as 'wp' fire and fixes the Query Monitor.
 */
class Setup_WordPress {
	/**
	 * Store if the admin bar was already setup.
	 *
	 * @var bool
	 */
	protected static $did_setup = false;

	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Handle an incoming request and setup the admin bar.
	 *
	 * @param Request  $request Request instance.
	 * @param \Closure $next Callback for the middleware.
	 * @return mixed
	 */
	public function handle( Request $request, Closure $next ) {
		global $wp;

		if ( ! static::$did_setup && $wp instanceof \WP && ! \did_action( 'template_redirect' ) ) {
			// Allow WP to set the current user and fire the 'WP' hook.
			$wp->init();

			/* Documented in wp-includes/class-wp.php */
			\do_action_ref_array( 'wp', [ $wp ] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			\_wp_admin_bar_init();
		}

		$response = $next( $request );

		// Store the response object for use in Query Monitor.
		$this->app->instance( 'response', $response );

		if ( ! static::$did_setup ) {
			static::$did_setup = true;

			$provider = $this->app->get_provider( Query_Monitor_Service_Provider::class );

			if ( $provider instanceof Query_Monitor_Service_Provider ) {
				$qm_output = $provider->fire_query_monitor_dispatches();

				if ( ! empty( $qm_output ) ) {
					$response->setContent( $response->getContent() . $qm_output );
				}
			}
		}

		unset( $this->app['response'] );

		return $response;
	}
}
