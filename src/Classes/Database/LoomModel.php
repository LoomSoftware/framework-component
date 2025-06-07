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
    protected string $alias = 't0';
    protected string $queryString = '';
    protected array $columns = [];
    protected array $wheres = [];
    protected array $joins = [];
    protected array $queryBindings = [];

    public function __construct()
    {
    }

    public static function setDatabaseConnection(DatabaseConnection $databaseConnection): void
    {
        static::$databaseConnection = $databaseConnection;
    }

    public static function select(array $columns = ['*']): static
    {
        $instance = new static;
        $instance->columns = $columns;

        return $instance;
    }

    public function innerJoin(string $table, string $alias): static
    {
        $this->joins[] = [$table, $alias];

        return $this;
    }

    public function where(string $column, mixed $value): static
    {
        $this->wheres[] = [$column, $value];

        return $this;
    }

    public function getQueryString(): string
    {
        $this->queryString = 'SELECT ' . implode(', ', $this->columns) . ' FROM ' . static::getTableName() . ' ' . $this->alias;

        foreach ($this->joins as $join) {
            $this->queryString .= ' INNER JOIN ' . $join[0] . ' ' . $join[1];
        }

        foreach ($this->wheres as $where) {
            $this->queryString .= ' WHERE ' . $where[0] . ' = ?';
        }

        $this->queryBindings = array_merge($this->queryBindings, $this->wheres);

        return $this->queryString;
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

    private static function getPropertyColumnMap(): array
    {
        $properties = [];
        $reflectionClass = new \ReflectionClass(static::class);

        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);

            if ($attributes) {
                $properties[$property->getName()] = $attributes[0]->getArguments()['name'];
            }
        }

        return $properties;
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