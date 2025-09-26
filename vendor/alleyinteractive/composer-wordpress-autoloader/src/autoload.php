<?php

/**
 * Autoload the wordpress-autoload.php file from the vendor directory.
 *
 * Used to automatically load the file when vendor/autoload.php is loaded.
 */

$autoloadFiles = [
    // Attempt to translate the /vendor/ in the directory path to the vendor directory.
    preg_replace('#/vendor/.*$#', '/vendor/wordpress-autoload.php', __DIR__),
    preg_replace('/\\\vendor\\\.*$/', '/vendor/wordpress-autoload.php', __DIR__),

    // Assuming this file is located at
    // vendor/alleyinteractive/composer-wordpress-autoloader/src/autoload.php,
    // hop up a few directories and try to load the file from there.
    dirname(__DIR__, 3) . '/wordpress-autoload.php',

    // Handle local development of the package where vendor directory is closer
    // to the root.
    '../../../vendor/wordpress-autoload.php',
    '../../vendor/wordpress-autoload.php',
];

foreach ($autoloadFiles as $path) {
    if ($path && !is_dir($path) && file_exists($path)) {
        require_once $path;
        break;
    }
}
