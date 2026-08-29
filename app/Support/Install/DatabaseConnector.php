<?php

declare(strict_types=1);

namespace App\Support\Install;

interface DatabaseConnector
{
    public function test(
        string $host,
        int $port,
        string $database,
        string $username,
        string $password,
    ): void;
}
