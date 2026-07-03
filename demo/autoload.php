<?php

/**
 * Minimal PSR-4 autoloader for the GenAI\Container namespace.
 *
 * Use this when you are not running Composer. If you are, just rely on
 * Composer's generated autoloader instead (see composer.json).
 *
 * The Container implements psr/container's ContainerInterface, so those
 * interfaces must be loadable too. We pull in the nearest Composer autoloader
 * when one exists (that is where psr/container lives); the PSR-4 fallback below
 * still covers GenAI\Container if the package itself is not Composer-installed.
 *
 * Compatible with PHP 5.3.29.
 */

$vendorCandidates = array(
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
);
foreach ($vendorCandidates as $vendorAutoload) {
    if (is_file($vendorAutoload)) {
        require $vendorAutoload;
        break;
    }
}

spl_autoload_register(function ($class) {
    $prefixes = array(
        'GenAI\\Container\\' => __DIR__ . '/../src/',
        'Cache\\'            => __DIR__ . '/cache/',   // the compiled container, cache/Container.php
    );

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (is_file($file)) {
            require $file;
        }
        return;
    }
});
