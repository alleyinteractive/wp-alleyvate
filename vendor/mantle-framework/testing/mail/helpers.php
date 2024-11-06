<?php
/**
 * Mail helper methods.
 *
 * Intentionally not namespaced to mirror core.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 *
 * @package Mantle
 */

use Mantle\Testing\Mail\Mock_Mailer;

/**
 * Helper method to return the global phpmailer instance defined in the bootstrap
 */
function tests_retrieve_phpmailer_instance(): \PHPMailer\PHPMailer\PHPMailer|bool {
	return $GLOBALS['phpmailer'] ?? false;
}

/**
 * Helper method to reset the phpmailer instance.
 */
function reset_phpmailer_instance(): void {
	$GLOBALS['phpmailer'] = new Mock_Mailer( true );
}
