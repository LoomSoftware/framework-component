<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database;

class LoomModel
{
    protected static ?DatabaseConnection $databaseConnection = null;

    public function __construct()
    {
        if (static::$databaseConnection === null) {
            throw new \Exception('Database connection not set');
        }
    }

    public static function setDatabaseConnection(DatabaseConnection $databaseConnection): void
    {
        static::$databaseConnection = $databaseConnection;
    }

    private static function getClassAttributes(): array
    {
        return new \ReflectionClass(static::class)->getAttributes();
    }
}