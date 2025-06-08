<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database;

use Loom\FrameworkComponent\Classes\Core\Helper\AttributeHelper;
use Loom\FrameworkComponent\Classes\Database\Attributes\Schema;
use Loom\FrameworkComponent\Classes\Database\Attributes\Table;
use Loom\FrameworkComponent\Classes\Database\Mapper\PropertyColumnMapper;
use Loom\FrameworkComponent\Classes\Database\Query\QueryBuilder;

abstract class LoomModel
{
    protected static ?DatabaseConnection $databaseConnection = null;
    protected ?QueryBuilder $queryBuilder = null;

    public function __construct()
    {
    }

    public static function setDatabaseConnection(DatabaseConnection $databaseConnection): void
    {
        static::$databaseConnection = $databaseConnection;
    }

    public static function select(array $columns = ['*'], string $alias = 't0'): static
    {
        $instance = new static();
        $instance->queryBuilder = new QueryBuilder(static::class, $alias)->select($columns);

        return $instance;
    }

    public function innerJoin(string $class, string $alias, array $conditions): static
    {
        if (!$this->queryBuilder) {
            return $this;
        }

        $this->queryBuilder->innerJoin($class, $alias, $conditions);

        return $this;
    }

    public function where(string $columnOrProperty, mixed $value): static
    {
        if (!$this->queryBuilder) {
            return $this;
        }

        $this->queryBuilder->where($columnOrProperty, $value);

        return $this;
    }

    /**
     * @return static[]
     *
     * @throws \ReflectionException
     */
    public function get(): array
    {
        if (!$this->queryBuilder) {
            return [];
        }

        $query = static::$databaseConnection?->getConnection()->prepare($this->queryBuilder->getQueryString());

        if ($query) {
            $query->execute();

            $queryResults = $query->fetchAll();

            $queryMap = [];
            $output = [];

            foreach ($queryResults as $queryResult) {
                $resultMap = [];

                foreach ($queryResult as $column => $value) {
                    $splitColumn = explode('_', $column);

                    $resultMap[$splitColumn[0]][$splitColumn[1]] = $value;
                }

                $queryMap[] = $resultMap;
            }

            foreach ($queryMap as $resultRow) {
                $modelInstance = new static;

                foreach ($resultRow as $model => $modelData) {
                    if (is_int($model)) {
                        continue;
                    }

                    $propertyColumnMap = PropertyColumnMapper::map(static::class);
                    $reflectionClass = new \ReflectionClass(static::class);

                    if ($model === $this->queryBuilder->getAlias()) {
                        foreach ($propertyColumnMap as $property => $column) {
                            if (!$reflectionClass->hasProperty($property)) {
                                continue;
                            }

                            $reflectionProperty = $reflectionClass->getProperty($property);

                            if (is_subclass_of($reflectionProperty->getType()->getName(), LoomModel::class)) {
                                continue;
                            }

                            $columnData = $modelData[$column] ?? $modelData[$property] ?? null;

                            if (!$columnData) {
                                continue;
                            }

                            if ($reflectionProperty->getType()->getName() === \DateTimeInterface::class) {
                                $columnData = new \DateTime($columnData);
                            }

                            if ($reflectionProperty->getType()->getName() === 'boolean') {
                                $columnData = (int) $columnData;
                            }

                            $modelInstance->$property = $columnData;
                        }
                    } else {
                        $staticReflectionClass = new \ReflectionClass(static::class);

                        foreach ($this->queryBuilder->getJoins() as $join) {
                            if ($join['alias'] !== $model) {
                                continue;
                            }

                            foreach ($join['conditions'] as $condition) {
                                $splitCondition = explode(' ', $condition);

                                foreach ($splitCondition as $conditionPart) {
                                    if (!str_contains($conditionPart, '.')) {
                                        continue;
                                    }

                                    $splitConditionPart = explode('.', $conditionPart);

                                    $splitConditionAlias = $splitConditionPart[0];

                                    if ($splitConditionAlias !== $this->queryBuilder->getAlias()) {
                                        continue;
                                    }

                                    $splitConditionColumn = $splitConditionPart[1];

                                    foreach ($propertyColumnMap as $property => $column) {
                                        if ($staticReflectionClass->getProperty($property)->getType()->getName() !== $join['model']) {
                                            continue;
                                        }

                                        if ($splitConditionColumn === $column || $splitConditionColumn === $property) {
                                            $joinInstance = new $join['model'];
                                            $joinModelPropertyColumnMap = PropertyColumnMapper::map($join['model']);

                                            foreach ($joinModelPropertyColumnMap as $joinProperty => $joinColumn) {
                                                if (isset($modelData[$joinColumn])) {
                                                    $joinInstance->$joinProperty = $modelData[$joinColumn];
                                                }

                                                if (isset($modelData[$joinProperty])) {
                                                    $joinInstance->$joinProperty = $modelData[$joinProperty];
                                                }
                                            }

                                            $modelInstance->$property = $joinInstance;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $output[] = $modelInstance;
            }

            return $output;
        }

        return [];
    }

    public function getOne(): ?static
    {
        return null;
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