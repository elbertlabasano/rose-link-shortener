<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class DatabaseSafetyTest extends TestCase
{
    public function test_explicit_mysql_connection_is_blocked_before_connecting(): void
    {
        config(['database.connections.unsafe' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'database' => 'rose_shortener']]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database safety guard');
        DB::connection('unsafe');
    }

    public function test_changing_default_connection_cannot_bypass_guard(): void
    {
        config(['database.default' => 'mysql']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database safety guard');
        DB::connection();
    }

    public function test_file_based_sqlite_connection_is_blocked(): void
    {
        config(['database.connections.disk' => ['driver' => 'sqlite', 'database' => database_path('database.sqlite')]]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database safety guard');
        DB::connection('disk');
    }
}
