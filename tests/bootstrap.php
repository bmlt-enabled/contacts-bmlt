<?php

/**
 * PHPUnit bootstrap file for Contacts BMLT plugin tests.
 *
 * Uses wp-phpunit/wp-phpunit (installed via Composer) as the test library.
 * WordPress core must be downloaded to WP_CORE_DIR (default: /tmp/wordpress).
 */

putenv('WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php');

$contactsBmltTestsDir = dirname(__DIR__) . '/vendor/wp-phpunit/wp-phpunit';

if (!file_exists("{$contactsBmltTestsDir}/includes/functions.php")) {
    echo 'Could not find wp-phpunit. Run: composer install' . PHP_EOL;
    exit(1);
}

require_once "{$contactsBmltTestsDir}/includes/functions.php";

tests_add_filter('muplugins_loaded', function () {
    require dirname(__DIR__) . '/contacts-bmlt.php';
});

require "{$contactsBmltTestsDir}/includes/bootstrap.php";
