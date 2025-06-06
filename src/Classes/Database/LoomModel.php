<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database;

use Loom\FrameworkComponent\Classes\Database\Attributes\Schema;
use Loom\FrameworkComponent\Classes\Database\Attributes\Table;

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

    /**
     * @throws \Exception
     */
    protected static function getSchemaAttribute(): Schema
    {
        $attributes = static::getClassAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute instanceof Schema) {
                return $attribute;
            }
        }

        throw new \Exception('Schema attribute not found');
    }

    /**
     * @throws \Exception
     */
    protected static function getTableAttribute(): Table
    {
        $attributes = static::getClassAttributes();
        var_dump($attributes);

        foreach ($attributes as $attribute) {
            var_dump($attribute);
            if ($attribute instanceof Table) {
                return $attribute;
            }
        }

        throw new \Exception('Table attribute not found');
    }

    private static function getClassAttributes(): array
    {
        return new \ReflectionClass(static::class)->getAttributes();
    }
}