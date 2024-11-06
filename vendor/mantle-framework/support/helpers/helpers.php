<?php
/**
 * Helper Functions for the Framework
 *
 * @package Mantle
 */

namespace Mantle\Support\Helpers;

// Bail early if the helper functions are already loaded.
if ( function_exists( __NAMESPACE__ . '\invalid_hook_removal' ) ) {
	return;
}

require_once __DIR__ . '/internals.php';
require_once __DIR__ . '/helpers-array.php';
require_once __DIR__ . '/helpers-core-objects.php';
require_once __DIR__ . '/helpers-environment.php';
require_once __DIR__ . '/helpers-general.php';
require_once __DIR__ . '/helpers-log.php';
require_once __DIR__ . '/helpers-rest-api.php';
require_once __DIR__ . '/helpers-validated-hook-removal.php';
