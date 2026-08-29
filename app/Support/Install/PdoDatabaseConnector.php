<?php

declare(strict_types=1);

namespace App\Support\Install;

use PDO;
use PDOException;

final class PdoDatabaseConnector implements DatabaseConnector
{
    public function test(
        string $host,
        int $port,
        string $database,
        string $username,
        string $password,
    ): void {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);

        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        $pdo->query('SELECT 1');
    }
}
