<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database;

use Loom\FrameworkComponent\Classes\Database\Attributes\Column;
use Loom\FrameworkComponent\Classes\Database\Attributes\ID;
use Loom\FrameworkComponent\Classes\Database\Attributes\Schema;
use Loom\FrameworkComponent\Classes\Database\Attributes\Table;

class LoomModel
{
    protected static ?DatabaseConnection $databaseConnection = null;
    protected static string $queryString = '';
    protected static array $queryBindings = [];

    public function __construct()
    {
    }

    public static function setDatabaseConnection(DatabaseConnection $databaseConnection): void
    {
        static::$databaseConnection = $databaseConnection;
    }

    public static function select(array $columns = ['*']): static
    {
        $columns = array_map(fn($column) => sprintf('%s.%s.%s', self::getSchemaName(), self::getTableName(), $column), $columns);
        self::$queryString = sprintf(
            'SELECT %s FROM %s.%s',
            implode(', ', $columns),
            self::getSchemaName(),
            self::getTableName()
        );

        return new static;
    }

    public static function queryString(): string
    {
        return self::$queryString;
    }

    protected static function getSchemaName(): ?string
    {
        $schemaName = static::getAttributeArgument(Schema::class, 'name');

        if (!$schemaName || !is_string($schemaName)) {
            return null;
        }

        return $schemaName;
    }

    protected static function getTableName(): ?string
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

    private static function getIdProperty(): ?string
    {
        $reflectionClass = new \ReflectionClass(static::class);

        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->getAttributes(ID::class)) {
                return $property->getName();
            }
        }

        return null;
    }

    private static function getIdColumn(): ?string
    {
        $reflectionClass = new \ReflectionClass(static::class);

        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->getAttributes(Column::class)) {
                return $property->getAttributes(Column::class)[0]->getArguments()['name'];
            }
        }

        return null;
    }

    private static function getClassAttributes(): array
    {
        return new \ReflectionClass(static::class)->getAttributes();
    }
}