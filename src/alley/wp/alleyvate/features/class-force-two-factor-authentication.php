<?php
/**
 * Class file for Force_Two_Factor_Authentication
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
 * Forces 2FA for users with Edit permissions or higher when 2FA is available.
 *
 * A lot of this is inspired by Automattic's checks for VIP Go.
 */
final class Force_Two_Factor_Authentication implements Feature {

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		if ( self::is_vip_environment() ) {
			add_filter( 'wpcom_vip_is_two_factor_forced', [ self::class, 'filter__wpcom_vip_is_two_factor_forced' ], PHP_INT_MAX );
			return;
		}

		// For non-VIP environments, we do the forcing ourselves.
		add_filter( 'map_meta_cap', [ self::class, 'filter__map_meta_cap' ], 0, 4 );

		add_action( 'admin_notices', [ self::class, 'action__admin_notices' ] );
	}

	/**
	 * For VIP environments we can skip all of the functionality, and only focus on returning true if the current user
	 * can edit posts.
	 *
	 * @return bool
	 */
	public static function filter__wpcom_vip_is_two_factor_forced(): bool {
		return self::force_to_enable_2fa();
	}

	/**
	 * Filter the user capabilities to restrict them to just those capabilities required to enabled two factor authentication.
	 *
	 * @param array  $caps    The user capabilities.
	 * @param string $cap     The currently active user capability.
	 * @param int    $user_id The user ID.
	 * @param array  $args    Context to the capability check.
	 */
	public static function filter__map_meta_cap( $caps, $cap, $user_id, $args ): array {
		if ( ! self::should_use_two_factor_authentication() ) {
			return $caps;
		}

		$subscriber_caps = [
			'read',
			'level_0',
		];

		if (
			'edit_user' === $cap &&
			! empty( $args ) &&
			(int) $user_id === (int) $args[0]
		) {
			$subscriber_caps[] = 'edit_user';
		}

		return in_array( $cap, $subscriber_caps, true ) ? $caps : [ 'do_not_allow' ];
	}

	/**
	 * Adds admin notices for the end user.
	 */
	public static function action__admin_notices(): void {
		if ( self::should_use_two_factor_authentication() ) {
			self::admin_notice__configure_two_factor();
		}

		if ( ! self::two_factor_plugin_active() ) {
			self::admin_notice__plugin_dependency();
		}
	}

	/**
	 * Adds an Admin Notice notifying the end user that they need to enable Two Factor authentication.
	 */
	public static function admin_notice__configure_two_factor(): void {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Two-factor authentication is required.', 'alley' ); ?></strong>
				<?php
					printf(
						wp_kses_post(
							/* translators: The Admin URL to the profile page. */
							__( 'Please <a href="%s">set up two-factor authentication</a> to continue.', 'alley' )
						),
						esc_url( admin_url( 'profile.php' ) )
					);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Adds an Admin Notice notifying administrators that they need to install the Two Factor authentication plugin.
	 * Only show to users who can activate plugins.
	 */
	public static function admin_notice__plugin_dependency(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Two-factor authentication plugin is required.', 'alley' ); ?></strong>
				<?php
					printf(
						wp_kses_post(
							/* translators: The URL to the Two Factor plugin in the WordPress.org repository. */
							__( 'Please install the <a href="%s">Two Factor plugin</a> to enable this feature.', 'alley' )
						),
						esc_url( 'https://wordpress.org/plugins/two-factor/' )
					);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Returns true if Two Factor Authentication should be enforced, false otherwise.
	 *
	 * This will be false if the environment is a local environment, if the user is not
	 * logged in, if the Two Factor plugin is not activated, or if the Two Factor plugin
	 * is active and 2fa is already enabled for this user account.
	 *
	 * @return bool
	 */
	private static function should_use_two_factor_authentication(): bool {
		/*
		 * Ignore 2fa for local environments, not logged in users, or VIP environments.
		 *
		 * Also don't try to enforce 2fa if we don't have the Two Factor plugin installed.
		 */
		return 'local' !== wp_get_environment_type() &&
				is_user_logged_in() &&
				self::force_to_enable_2fa() &&
				self::two_factor_plugin_active() &&
				! self::two_factor_already_in_use();
	}

	/**
	 * Determine whether the user should be forced to enable 2fa before interacting with site.
	 *
	 * @return bool
	 */
	private static function force_to_enable_2fa(): bool {
		$capability_min = apply_filters( 'alleyvate_force_2fa_capability', 'edit_posts' );

		// Remove the filter to avoid infinite loops.
		$removed = remove_filter( 'map_meta_cap', [ self::class, 'filter__map_meta_cap' ], 0 );

		$result = current_user_can( $capability_min );

		if ( $removed ) {
			add_filter( 'map_meta_cap', [ self::class, 'filter__map_meta_cap' ], 0, 4 );
		}

		return $result;
	}

	/**
	 * Returns true if the user is using the Two Factor plugin, and 2fa is already enabled for the user.
	 *
	 * @return bool
	 */
	private static function two_factor_already_in_use(): bool {
		return self::two_factor_plugin_active() && \Two_Factor_Core::is_user_using_two_factor();
	}

	/**
	 * Determine if the Two Factor plugin is activated.
	 *
	 * @return bool
	 */
	private static function two_factor_plugin_active(): bool {
		return class_exists( 'Two_Factor_Core' );
	}

	/**
	 * Determine if the site is loaded in a VIP environment.
	 *
	 * @return bool
	 */
	private static function is_vip_environment(): bool {
		return defined( 'VIP_GO_APP_ENVIRONMENT' );
	}
}
