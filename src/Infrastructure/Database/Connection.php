<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $connection = null;

    public static function getInstance(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::createConnection();
        }
        return self::$connection;
    }

    private static function createConnection(): PDO
    {
        $config = require __DIR__ . '/../../config/database.php';
        $mysqlConfig = $config['connections']['mysql'];

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $mysqlConfig['host'],
            $mysqlConfig['port'],
            $mysqlConfig['database'],
            $mysqlConfig['charset']
        );

        try {
            $pdo = new PDO(
                $dsn,
                $mysqlConfig['username'],
                $mysqlConfig['password'],
                $mysqlConfig['options']
            );
            return $pdo;
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    public static function close(): void
    {
        self::$connection = null;
    }
}
