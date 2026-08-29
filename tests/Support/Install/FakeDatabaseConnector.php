<?php

declare(strict_types=1);

namespace Tests\Support\Install;

use App\Support\Install\DatabaseConnector;

final class FakeDatabaseConnector implements DatabaseConnector
{
    public function __construct(
        private bool $shouldSucceed = true,
    ) {}

    public function test(
        string $host,
        int $port,
        string $database,
        string $username,
        string $password,
    ): void {
        if (! $this->shouldSucceed) {
            throw new \RuntimeException('Connection failed');
        }
    }
}
