<?php

namespace Tests\Unit;

use App\Support\TestDatabaseSafety;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TestDatabaseSafetyTest extends TestCase
{
    public function test_only_in_memory_sqlite_is_allowed(): void
    {
        TestDatabaseSafety::assertSafe(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->addToAssertionCount(1);
    }

    public static function unsafeConnections(): array
    {
        return [
            'live mysql' => [['driver' => 'mysql', 'database' => 'rose_shortener']],
            'test mysql also blocked' => [['driver' => 'mysql', 'database' => 'rose_shortener_test']],
            'sqlite backup' => [['driver' => 'sqlite', 'database' => '/database/database.sqlite']],
            'missing config' => [[]],
            'URL override' => [['driver' => 'sqlite', 'database' => ':memory:', 'url' => 'mysql://user:pass@localhost/rose_shortener']],
            'write override' => [['driver' => 'sqlite', 'database' => ':memory:', 'write' => ['database' => 'database.sqlite']]],
            'read override' => [['driver' => 'sqlite', 'database' => ':memory:', 'read' => ['database' => 'database.sqlite']]],
        ];
    }

    #[DataProvider('unsafeConnections')]
    public function test_persistent_connections_are_rejected(array $config): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database safety guard');
        TestDatabaseSafety::assertSafe($config);
    }
}
