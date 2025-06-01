<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database;

class DatabaseConnection
{
    private \PDO $connection;

    /**
     * @throws \Exception
     */
    public function __construct(string $dsn, string $username, string $password)
    {
        try {
            $this->connection = new \PDO($dsn, $username, $password);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception('Database connection failed: '. $e->getMessage());
        }
    }

    public function getConnection(): \PDO
    {
        return $this->connection;
    }
}