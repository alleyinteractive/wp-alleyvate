<?php
/**
 * Forced Jetpack SSO feature
 *
 * (c) Alley <info@alley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wp-alleyvate
 */

namespace Alley\WP\Alleyvate;

/*
 * A more strict extension to the Jetpack SSO feature that disables the standard WordPress username/password login
 * boxes in favor of forcing login using WordPress.com. Defined separately from the Jetpack SSO feature to enable this
 * behavior to be selectively disabled while retaining the other Jetpack SSO settings.
 */
add_filter( 'jetpack_remove_login_form', '__return_true' );
