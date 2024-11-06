<?php //phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
/**
 * Asset_Assertions trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

/**
 * Assorted Test_Cast assertions.
 */
trait Asset_Assertions {
	/**
	 * Assert that a script is matching a specific status.
	 *
	 * @param string $handle Script handle.
	 * @param string $status Script status.
	 */
	public function assertScriptStatus( string $handle, string $status ): void {
		// Fire the enqueue scripts hook to ensure the scripts are loaded.
		if ( ! did_action( 'wp_enqueue_scripts' ) ) {
			do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		$this->assertTrue( wp_script_is( $handle, $status ), "Script {$handle} is not {$status}" );
	}

	/**
	 * Assert that a style is matching a specific status.
	 *
	 * @param string $handle Style handle.
	 * @param string $status Style status.
	 */
	public function assertStyleStatus( string $handle, string $status ): void {
		// Fire the enqueue scripts hook to ensure the scripts are loaded.
		if ( ! did_action( 'wp_enqueue_scripts' ) ) {
			do_action( 'wp_enqueue_scripts' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		$this->assertTrue( wp_style_is( $handle, $status ), "Style {$handle} is not {$status}" );
	}

	/**
	 * Assert that a script is enqueued.
	 *
	 * @param string $handle Script handle.
	 */
	public function assertScriptEnqueued( string $handle ): void {
		$this->assertScriptStatus( $handle, 'enqueued' );
	}

	/**
	 * Assert that a script is not enqueued.
	 *
	 * @param string $handle Script handle.
	 */
	public function assertScriptNotEnqueued( string $handle ): void {
		$this->assertScriptStatus( $handle, 'registered' );
	}

	/**
	 * Assert that a style is enqueued.
	 *
	 * @param string $handle Style handle.
	 */
	public function assertStyleEnqueued( string $handle ): void {
		$this->assertStyleStatus( $handle, 'enqueued' );
	}

	/**
	 * Assert that a style is not enqueued.
	 *
	 * @param string $handle Style handle.
	 */
	public function assertStyleNotEnqueued( string $handle ): void {
		$this->assertStyleStatus( $handle, 'registered' );
	}

	/**
	 * Assert that a script is registered.
	 *
	 * @param string $handle Script handle.
	 */
	public function assertScriptRegistered( string $handle ): void {
		$this->assertScriptStatus( $handle, 'registered' );
	}

	/**
	 * Assert that a style is registered.
	 *
	 * @param string $handle Style handle.
	 */
	public function assertStyleRegistered( string $handle ): void {
		$this->assertStyleStatus( $handle, 'registered' );
	}
}
