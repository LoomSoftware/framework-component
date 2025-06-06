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
    }

    public static function setDatabaseConnection(DatabaseConnection $databaseConnection): void
    {
        static::$databaseConnection = $databaseConnection;
    }

    public static function getSchemaName(): ?string
    {
        $schemaName = static::getAttributeArgument(Schema::class, 'name');

        if (!$schemaName || !is_string($schemaName)) {
            return null;
        }

        return $schemaName;
    }

    public static function getTableName(): ?string
    {
        $tableName = static::getAttributeArgument(Table::class, 'name');

        if (!$tableName || !is_string($tableName)) {
            return null;
        }

        return $tableName;
    }

    private static function getAttributeArgument(string $attributeName, string $argument): mixed
    {
        $attributes = static::getClassAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->name === $attributeName) {
                $arguments = $attribute->getArguments();

                if (array_key_exists($argument, $arguments)) {
                    return $arguments[$argument];
                }
            }
        }

        return null;
    }

    private static function getClassAttributes(): array
    {
        return new \ReflectionClass(static::class)->getAttributes();
    }
}