<?php

/**
 * The theme's test bootstrap.
 *
 * Resolves core's first-party test library from WP_TESTS_DIR and the test
 * configuration from WP_TESTS_CONFIG_FILE_PATH. A missing variable falls back
 * to the Phase 0 harness locations on this workstation and is overridable;
 * they are a publishing item, not a contract.
 *
 * Bootstrap::run() is called exactly once, from muplugins_loaded: calling it
 * per test would re-register every provider and module, and the second call
 * is the one that fails.
 */

declare(strict_types=1);

// Every statement is observable through wpdb's own buffer, which is how the
// query-ceiling assertions read the exact statements a Surface ran.
if (!defined('SAVEQUERIES')) {
    define('SAVEQUERIES', true);
}

$testsDir = getenv('WP_TESTS_DIR');
if (false === $testsDir || '' === $testsDir) {
    $testsDir = 'F:/kerjaan 2/WordPress/libraries/wordpress-develop/tests/phpunit';
}

if (!file_exists($testsDir.'/includes/bootstrap.php')) {
    fwrite(STDERR, sprintf('WP_TESTS_DIR does not contain includes/bootstrap.php: %s%s', $testsDir, PHP_EOL));
    exit(1);
}

$configFile = getenv('WP_TESTS_CONFIG_FILE_PATH');
if (false === $configFile || '' === $configFile) {
    $local = __DIR__.'/wp-tests-config.php';
    $configFile = is_file($local) ? $local : __DIR__.'/wp-tests-config.php.dist';
}

define('WP_TESTS_CONFIG_FILE_PATH', $configFile);
define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname(__DIR__).'/vendor/yoast/phpunit-polyfills');

require_once dirname(__DIR__).'/vendor/autoload.php';
require_once $testsDir.'/includes/functions.php';

$GLOBALS['howdah_boot_translations'] = [];

tests_add_filter(
    'muplugins_loaded',
    static function (): void {
        // The negative proof for translation timing: while the composition
        // root boots, no translated string may be produced from the theme's
        // own code. The recorder notes any gettext call whose caller frame
        // is inside the theme's app/ directory; BootI18nTest asserts it
        // stayed empty.
        $recorder = static function ($translation, $text = '') {
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10) as $frame) {
                $file = (string) ($frame['file'] ?? '');

                if (str_contains($file, '/app/') || str_contains($file, '\\app\\')) {
                    $GLOBALS['howdah_boot_translations'][] = $file.':'.($frame['line'] ?? 0).' '.(string) $text;
                    break;
                }
            }

            return $translation;
        };

        add_filter('gettext', $recorder, 10, 2);
        add_filter('gettext_with_context', $recorder, 10, 2);

        // The theme is the system under test; production's entry point runs
        // here, exactly once, for the whole suite.
        Iniznet\Howdah\Bootstrap::run();

        remove_filter('gettext', $recorder, 10);
        remove_filter('gettext_with_context', $recorder, 10);

        // The theme's tables come from the same migrations production runs on
        // a theme switch; a test suite is a first install.
        Iniznet\Howdah\Bootstrap::services()
            ->get(Iniznet\Mahout\Db\MigrationRunner::class)
            ->migrate();
    }
);

// Core's installer is a subprocess that prints its progress. The buffer is
// discarded so nothing is sent before PHPUnit starts.
\ob_start();
require_once $testsDir.'/includes/bootstrap.php';
\ob_end_clean();
