<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database;

use Loom\FrameworkComponent\Classes\Core\Helper\AttributeHelper;
use Loom\FrameworkComponent\Classes\Database\Attributes\Schema;
use Loom\FrameworkComponent\Classes\Database\Attributes\Table;

abstract class LoomModel
{
    protected static ?DatabaseConnection $databaseConnection = null;

    public function __construct()
    {
    }

    public static function setDatabaseConnection(DatabaseConnection $databaseConnection): void
    {
        static::$databaseConnection = $databaseConnection;
    }

    /**
     * @throws \ReflectionException
     */
    public static function getSchemaName(): ?string
    {
        $schemaName = AttributeHelper::getAttributeValue(
            static::class,
            Schema::class,
            'name'
        );

        return $schemaName && is_string($schemaName) ? $schemaName : null;
    }

    /**
     * @throws \ReflectionException
     */
    public static function getTableName(): ?string
    {
        $tableName = AttributeHelper::getAttributeValue(
            static::class,
            Table::class,
            'name'
        );

        return $tableName && is_string($tableName) ? $tableName : null;
    }
}