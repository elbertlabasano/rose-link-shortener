<?php

namespace App\Support;

use Illuminate\Database\Connectors\ConnectionFactory;

final class SafeTestConnectionFactory extends ConnectionFactory
{
    public function make(array $config, $name = null)
    {
        // Check before Laravel creates a PDO connection, including secondary connections.
        TestDatabaseSafety::assertSafe($config);

        return parent::make($config, $name);
    }
}
