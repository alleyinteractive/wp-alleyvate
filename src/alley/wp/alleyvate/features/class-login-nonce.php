<?php
/**
 * Class file for Login_Nonce
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate\Features;

use Alley\WP\Alleyvate\Feature;

/**
 * Adds a nonce field to the login form.
 *
 * Heavily inspired by `wp-login-nonce` by `elyobo`
 *
 * @link https://github.com/elyobo/wp-login-nonce
 */
final class Login_Nonce implements Feature {

	/**
	 * The name to use for the nonce.
	 *
	 * @var string
	 */
	private string $nonce_name;

	/**
	 * The action to use for the nonce.
	 *
	 * @var string
	 */
	private string $nonce_action;

	/**
	 * The nonce lifetime. Stored in seconds.
	 *
	 * @var int
	 */
	private int $nonce_timeout;

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'login_init', [ $this, 'initialize_nonce_fields' ] );
		add_action( 'login_head', [ $this, 'add_meta_refresh' ] );
		add_action( 'login_form', [ $this, 'add_nonce_to_form' ] );
		add_filter( 'authenticate', [ $this, 'validate_login_nonce' ], 9999, 3 );
	}

	/**
	 * Add a meta refresh to the login page, so it will refresh after the nonce timeout.
	 */
	public function add_meta_refresh(): void {
		if ( ! ( (bool) apply_filters( 'alleyvate_nonce_meta_refresh', true ) ) ) {
			return;
		}

		echo sprintf( '<meta http-equiv="refresh" content="%d">', esc_attr( $this->nonce_timeout ) );
	}

	/**
	 * Add the nonce field to the form.
	 */
	public function add_nonce_to_form(): void {
		wp_nonce_field( $this->nonce_action, $this->nonce_name );
	}

	/**
	 * Initializes the nonce fields. Is only run on `login_init` to restrict nonce data to login page.
	 */
	public function initialize_nonce_fields(): void {
		/**
		 * Filters the nonce name.
		 *
		 * @param string $nonce_name The nonce name.
		 */
		$this->nonce_name = (string) apply_filters( 'alleyvate_nonce_name', $this->generate_random_nonce_name( 'alleyvate_login_nonce' ) );

		/**
		 * Filters the nonce action name.
		 *
		 * @param string $nonce_action The action name.
		 */
		$this->nonce_action = (string) apply_filters( 'alleyvate_nonce_action', 'alleyvate_login_action' );

		/**
		 * Filters the lifetime of the nonce, in minutes.
		 *
		 * Converted to seconds before storage.
		 *
		 * @param int $timeout The lifetime of the nonce, in minutes. Default 30.
		 */
		$this->nonce_timeout = ( (int) apply_filters( 'alleyvate_nonce_timeout', 30 ) ) * 60;

		add_filter( 'nonce_life', fn() => $this->nonce_timeout );
	}

	/**
	 * Validates the passed nonce is valid. Returns a user object on valid login,
	 * or void on invalid nonce.
	 *
	 * @param \WP_User|\WP_Error|null $user     The result of previous login validations. An instance of
	 *                                          WP_User if all validations have passed previously.
	 * @param string                  $username The username used to try to login.
	 * @param string                  $password The password used to try to login.
	 * @return \WP_User|\WP_Error
	 */
	public function validate_login_nonce( $user, $username, $password ) {
		/*
		 * If the filter is returning a \WP_Error, then validation has already failed.
		 * No need to check the nonce.
		 */
		if ( $user instanceof \WP_Error ) {
			return $user;
		}

		/*
		 * We can't be sure when this filter will be triggered. Since we always need
		 * this filter triggered last, in the case of `$user` coming through as null
		 * lets run authentications again, and make sure.
		 *
		 * In a perfect world, this block will never run, but if it needs to be run,
		 * we want it to run.
		 */
		if ( null === $user ) {
			// Remove this check to avoid infinite loops.
			remove_filter( 'authenticate', [ $this, 'validate_login_nonce' ], 9999 );
			$user = apply_filters( 'authenticate', $user, $username, $password ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		// We're sure about this value now. Exit early.
		if ( ! $user instanceof \WP_User ) {
			return $user;
		}

		/*
		 * Now that we've manually run all of the authentication filters,
		 * and we know we have a valid login attempt, remove all the filters
		 * so we don't risk bypassing our nonce validation down the line.
		 */
		remove_all_filters( 'authenticate' );

		// If no post data exists, no nonce data exists, which means our login is invalid.
		if ( empty( $_POST ) ) {
			return new \WP_Error(
				'nonce_failure',
				__( 'Login attempt timed out. Please try again.', 'alley' )
			);
		}

		$nonce = false;

		if ( ! empty( $_POST[ $this->nonce_name ] ) ) {
			$nonce = sanitize_key( $_POST[ $this->nonce_name ] );
		}

		if ( ! $nonce ) {
			return new \WP_Error(
				'nonce_failure',
				__( 'Login attempt timed out. Please try again.', 'alley' )
			);

		}

		$nonce_validation = wp_verify_nonce( $nonce, $this->nonce_action );

		if ( ! $nonce_validation ) {
			return new \WP_Error(
				'nonce_failure',
				__( 'Login attempt timed out. Please try again.', 'alley' )
			);
		}

		return $user;
	}

	/**
	 * Randomize the nonce name using the data from the $_SERVER super global, and a provided salt.
	 *
	 * @param string $name The salt value.
	 * @return string
	 */
	private function generate_random_nonce_name( string $name ): string {
		$parts = [ $name ];
		if ( ! empty( $_SERVER ) ) { // phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders
			foreach ( [ 'REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP' ] as $key ) {
				$value   = ! empty( $_SERVER[ $key ] ) ? sanitize_key( $_SERVER[ $key ] ) : '';
				$parts[] = "{$key}={$value}";
			}
		}
		return hash( 'sha256', implode( '-', $parts ) );
	}
}
