<?php
/**
 * Isolated_Service_Provider interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Support;

/**
 * Isolated Service Provider
 *
 * A service provider that is isolated from the dependency on WordPress. This
 * service provider will be registered when the application is running in
 * isolation mode (a non-WordPress environment). The main use case is for
 * service providers that are required when running vendor/bin/mantle versus the
 * 'wp mantle' WP-CLI command.
 */
interface Isolated_Service_Provider {
}
