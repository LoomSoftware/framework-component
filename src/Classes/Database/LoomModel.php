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
            if ($attribute->name === Schema::class) {
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

        foreach ($attributes as $attribute) {
            if ($attribute->name === Table::class) {
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