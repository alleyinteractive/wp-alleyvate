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

use Alley\WP\Types\Feature;

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
	public const NONCE_NAME = 'wp_alleyvate_login_nonce';

	/**
	 * The action to use for the nonce.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'alleyvate_login_action';

	/**
	 * The nonce lifetime. Stored in seconds.
	 *
	 * @var int
	 */
	public const NONCE_TIMEOUT = 1800;

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'login_form_login', [ self::class, 'action__add_nonce_life_filter' ] );
		add_action( 'login_form_expired', [ self::class, 'action__prep_expired_form' ] );
		add_action( 'login_head', [ self::class, 'action__add_meta_refresh' ] );
		add_action( 'after_setup_theme', [ self::class, 'action__pre_validate_login_nonce' ], 9999 );
		add_filter( 'nocache_headers', [ self::class, 'add_no_store_to_login' ] );
	}

	/**
	 * Adds the `no-store` flag to the `Cache-Control` headers.
	 *
	 * @param array<string, string> $headers The headers array.
	 * @return array<string, string>
	 */
	public static function add_no_store_to_login( $headers ): array {
		if ( ! \is_array( $headers ) ) {
			$headers = [];
		}

		if ( 'wp-login.php' !== ( $GLOBALS['pagenow'] ?? '' ) ) {
			return $headers;
		}

		$headers['Cache-Control'] = 'no-cache, must-revalidate, max-age=0, no-store';

		return $headers;
	}

	/**
	 * Add a meta refresh to the login page, so it will refresh after the nonce timeout.
	 */
	public static function action__add_meta_refresh(): void {
		printf( '<meta http-equiv="refresh" content="%d">', esc_attr( (string) self::NONCE_TIMEOUT ) );
		?>
		<script>
			window.addEventListener('pageshow', (event) => {
				if (event.persisted) {
					location.reload();
				}
			});
		</script>
		<?php
	}

	/**
	 * Add the nonce field to the form.
	 *
	 * @see action__add_nonce_life_filter()
	 */
	public static function action__add_nonce_to_form(): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
	}

	/**
	 * Add a filter to change the nonce lifetime.
	 *
	 * Changing the lifetime of the nonce changes the actual nonce value. It all comes down to how WordPress actually generates the nonce.
	 * So only run on `login_form_login` to restrict to the login action, without affecting other wp-login actions.
	 *
	 * @see <https://github.com/WordPress/wordpress-develop/blob/94b70f1ae065f10937c22b2d4b180ceade1ddeee/src/wp-login.php#L482-L495>
	 */
	public static function action__add_nonce_life_filter(): void {
		add_filter( 'nonce_life', [ __CLASS__, 'nonce_life_filter' ], 10, 2 );
		add_action( 'login_form', [ __CLASS__, 'action__add_nonce_to_form' ] );
	}

	/**
	 * Filter the nonce timeout.
	 *
	 * @param int        $nonce_lifetime The lifetime of the nonce in seconds.
	 * @param string|int $action The nonce action, or -1 if none was provided.
	 * @return int
	 */
	public static function nonce_life_filter( $nonce_lifetime, $action ): int {
		if ( self::NONCE_ACTION !== $action ) {
			return $nonce_lifetime;
		}

		return self::NONCE_TIMEOUT;
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
			'wp-login.php' !== ( $GLOBALS['pagenow'] ?? '' ) ||
			empty( $_POST['pwd'] )
		) {
			return;
		}

		/*
		 * Nonce life is used to generate the nonce value. If this differs from the form,
		 * the nonce will not validate.
		 */
		add_filter( 'nonce_life', [ __CLASS__, 'nonce_life_filter' ], 10, 2 );

		$nonce = sanitize_key( $_POST[ self::NONCE_NAME ] ?? '' );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			// If the nonce is invalid, redirect to the login form with an error.
			// @phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_safe_redirect( add_query_arg( 'action', 'expired', wp_login_url( $_REQUEST['redirect_to'] ?? '' ) ) );
			exit;
		}

		/*
		 * Clean up after ourselves.
		 */
		remove_filter( 'nonce_life', [ __CLASS__, 'nonce_life_filter' ] );
	}

	/**
	 * Prepare the login form following a failed nonce check.
	 */
	public static function action__prep_expired_form(): void {
		add_filter( 'wp_login_errors', [ __CLASS__, 'filter__expired_login_error' ] );
		self::action__add_nonce_life_filter();
	}

	/**
	 * Add the expired message to the login screen.
	 *
	 * @return \WP_Error Error message for a bad nonce.
	 */
	public static function filter__expired_login_error(): \WP_Error {
		return new \WP_Error( 'nonce_error', __( 'The login form was expired, please try again.', 'alley' ) );
	}
}
