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
    private ?array $propertyColumnMap = null;

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
            $query->execute($this->queryBuilder->getParameters());
            $queryMap = $this->mapQueryResultsWithAlias($query->fetchAll());
            $output = [];

            foreach ($queryMap as $resultRow) {
                $modelInstance = new static;

                foreach ($resultRow as $model => $modelData) {
                    if (is_int($model)) {
                        continue;
                    }

                    $modelInstance = $this->mapToModel($model, $modelData, $modelInstance);
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

    private function mapToModel(string $alias, array $modelData, LoomModel $modelInstance): LoomModel
    {
        if ($alias === $this->queryBuilder->getAlias()) {
            $reflectionClass = new \ReflectionClass(static::class);

            foreach ($modelData as $property => $value) {
                if (!$reflectionClass->hasProperty($property)) {
                    continue;
                }

                try {
                    $value = $this->convertDatabaseValueToProperty($reflectionClass, $property, $value);

                    $modelInstance->$property = $value;
                } catch (\Exception $e) {
                    continue;
                }
            }

            return $modelInstance;
        }

        foreach ($this->queryBuilder->getJoins() as $join) {
            if ($alias !== $join['alias']) {
                continue;
            }

            foreach ($join['conditions'] as $condition) {
                foreach (explode(' ', $condition) as $conditionPart) {
                    if (!str_contains($conditionPart, '.')) {
                        continue;
                    }

                    $conditionAlias = explode('.', $conditionPart)[0];

                    if ($conditionAlias !== $this->queryBuilder->getAlias()) {
                        continue;
                    }

                    $conditionColumn = explode('.', $conditionPart)[1];


                    $joinInstance = new $join['model'];
                    $joinReflectionClass = new \ReflectionClass($join['model']);
                    $joinModelPropertyColumnMap = PropertyColumnMapper::map($join['model']);

                    foreach ($joinModelPropertyColumnMap as $joinProperty => $joinColumn) {
                        $returnDataColumnValue = $modelData[$joinColumn] ?? $modelData[$joinProperty] ?? null;

                        if (!$returnDataColumnValue) {
                            continue;
                        }

                        try {
                            $value = $this->convertDatabaseValueToProperty($joinReflectionClass, $joinProperty, $returnDataColumnValue);

                            $joinInstance->$joinProperty = $value;
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    $modelInstance->$conditionColumn = $joinInstance;
                }
            }
        }

        return $modelInstance;
    }

    /**
     * @throws \DateMalformedStringException|\ReflectionException
     */
    private function convertDatabaseValueToProperty(\ReflectionClass $class, string $property, string|int $value)
    {
        $reflectionProperty = $class->getProperty($property);

        if (is_subclass_of($reflectionProperty->getType()->getName(), LoomModel::class)) {
            throw new \Exception('Skip');
        }

        if ($reflectionProperty->getType()->getName() === \DateTimeInterface::class) {
            $value = new \DateTime($value);
        }

        if ($reflectionProperty->getType()->getName() === 'bool') {
            $value = (bool) $value;
        }

        return $value;
    }

    private function getPropertyColumnMap(): array
    {
        if ($this->propertyColumnMap) {
            return $this->propertyColumnMap;
        }

        $this->propertyColumnMap = PropertyColumnMapper::map(static::class);

        return $this->propertyColumnMap;
    }

    private function mapQueryResultsWithAlias(array $rawResults): array
    {
        $map = [];

        foreach ($rawResults as $rawResult) {
            $resultMap = [];

            foreach ($rawResult as $column => $value) {
                $splitColumn = explode('_', $column);

                $resultMap[$splitColumn[0]][$splitColumn[1]] = $value;
            }

            $map[] = $resultMap;
        }

        return $map;
    }
}