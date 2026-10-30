<?php

namespace App\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConfigurationUrlParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TestDatabaseSafety
{
    public static function isTestProcess(Application $app): bool
    {
        // PHPUnit detection also protects against cached APP_ENV=local/production.
        return $app->environment('testing') || defined('PHPUNIT_COMPOSER_INSTALL')
            || class_exists(TestCase::class, false);
    }

    public static function assertSafe(array $config): void
    {
        $config = (new ConfigurationUrlParser)->parseConfiguration($config);

        if (($config['driver'] ?? null) !== 'sqlite'
            || ($config['database'] ?? null) !== ':memory:'
            || isset($config['read']) || isset($config['write'])) {
            throw new RuntimeException(
                'Database safety guard: automated tests may use only SQLite :memory:. '
                .'MySQL and file databases are blocked before connecting. '
                .'Check phpunit.xml, environment variables, and cached configuration.'
            );
        }
    }
}
