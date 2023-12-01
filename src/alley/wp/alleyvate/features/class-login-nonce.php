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
	private const NONCE_SALT = 'wp_alleyvate_login_nonce';

	/**
	 * The action to use for the nonce.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'alleyvate_login_action';

	/**
	 * The nonce lifetime. Stored in seconds.
	 *
	 * @var int
	 */
	private const NONCE_TIMEOUT = 1800;

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'login_init', [ self::class, 'action__add_nonce_life_filter' ] );
		add_action( 'login_head', [ self::class, 'action__add_meta_refresh' ] );
		add_action( 'login_form', [ self::class, 'action__add_nonce_to_form' ] );
		add_action( 'after_setup_theme', [ self::class, 'action__pre_validate_login_nonce' ], 9999 );
	}

	/**
	 * Add a meta refresh to the login page, so it will refresh after the nonce timeout.
	 */
	public static function action__add_meta_refresh(): void {
		echo sprintf( '<meta http-equiv="refresh" content="%d">', esc_attr( self::NONCE_TIMEOUT ) );
	}

	/**
	 * Add the nonce field to the form.
	 */
	public static function action__add_nonce_to_form(): void {
		wp_nonce_field( self::NONCE_ACTION, self::generate_random_nonce_name( self::NONCE_SALT ) );
	}

	/**
	 * Initializes the nonce fields. Is only run on `login_init` to restrict nonce data to login page.
	 */
	public static function action__add_nonce_life_filter(): void {
		add_filter( 'nonce_life', fn() => self::NONCE_TIMEOUT );
	}

	/**
	 * Validates the login nonce as early as possible to avoid login attempts.
	 */
	public static function action__pre_validate_login_nonce(): void {
		/*
		 * If this request is not specifically a login attempt on the wp-login.php page,
		 * then skip it.
		 */
		if (
			'wp-login.php' !== $GLOBALS['pagenow'] ||
			empty( $_POST ) ||
			(
				isset( $_POST['wp-submit'] ) &&
				'Log In' !== $_POST['wp-submit']
			)
		) {
			return;
		}

		$nonce_life_filter = fn() => self::NONCE_TIMEOUT;

		/*
		 * Nonce life is used to generate the nonce value. If this differs from the form,
		 * the nonce will not validate.
		 */
		add_filter( 'nonce_life', $nonce_life_filter );

		$nonce = false;
		if ( ! empty( $_POST[ self::generate_random_nonce_name( self::NONCE_SALT ) ] ) ) {
			$nonce = sanitize_key( $_POST[ self::generate_random_nonce_name( self::NONCE_SALT ) ] );
		}

		if (
			! $nonce ||
			! wp_verify_nonce( $nonce, self::NONCE_ACTION )
		) {
			// This is a login with an invalid nonce. Throw an error.
			http_response_code( 403 );
			die;
		}

		/*
		 * Clean up after ourselves.
		 */
		remove_filter( 'nonce_life', $nonce_life_filter );
	}

	/**
	 * Randomize the nonce name using the data from the $_SERVER super global, and a provided salt.
	 *
	 * @param string $name The salt value.
	 * @return string
	 */
	public static function generate_random_nonce_name( string $name ): string {
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
