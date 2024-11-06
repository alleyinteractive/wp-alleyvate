<?php
/**
 * This file contains assorted log helpers.
 *
 * @package Mantle
 */

namespace Mantle\Support\Helpers;

use Psr\Log\LoggerInterface;

/**
 * Write some information to the log.
 *
 * @param  string $message Log message.
 * @param  array  $context Log context.
 */
function info( string $message, array $context = [] ): void {
	app( 'log' )->info( $message, $context );
}

/**
 * Send a message to the logger.
 *
 * If no parameters are passed, the logger instance is returned.
 *
 * @param  string|null $message Log message, optional.
 * @param  array       $context Log context, optional.
 */
function logger( string $message = null, array $context = [] ): ?LoggerInterface {
	if ( is_null( $message ) ) {
		return app( 'log' );
	}

	return app( 'log' )->debug( $message, $context );
}
