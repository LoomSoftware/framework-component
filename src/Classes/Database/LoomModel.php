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

    protected static function getSchemaName(): ?string
    {
        $attributes = static::getClassAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->name === Schema::class) {
                return $attribute->getName();
            }
        }

        return null;
    }

    protected static function getTableName(): ?string
    {
        $attributes = static::getClassAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->name === Table::class) {
                var_dump($attribute->getArguments());
                return $attribute->getName();
            }
        }

        return null;
    }

    private static function getClassAttributes(): array
    {
        return new \ReflectionClass(static::class)->getAttributes();
    }
}