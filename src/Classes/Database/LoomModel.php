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

    public function getQueryBuilder(): ?QueryBuilder
    {
        return $this->queryBuilder;
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

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        if (!$this->queryBuilder) {
            return $this;
        }

        $this->queryBuilder->orderBy($column, $direction);

        return $this;
    }

    /**
     * @return static[]
     */
    public function get(): array
    {
        try {
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
                    $associatedModels = [];

                    foreach ($resultRow as $model => $modelData) {
                        if (is_int($model)) {
                            continue;
                        }

                        if ($model === $this->queryBuilder->getAlias()) {
                            $modelInstance = $this->mapToModel($model, $modelData, $modelInstance);
                            continue;
                        }

                        $associatedJoin = array_values(array_filter($this->queryBuilder->getJoins(), function ($join) use ($model) {
                            return $join['alias'] === $model;
                        }));

                        if (!$associatedJoin) {
                            continue;
                        }

                        $associatedModel = new $associatedJoin[0]['model'];

                        foreach (PropertyColumnMapper::map($associatedJoin[0]['model']) as $property => $column) {
                            $columnValue = $modelData[$column] ?? $modelData[$property] ?? null;

                            if (!$columnValue) {
                                continue;
                            }

                            try {
                                $associatedModel->$property = $this->convertDatabaseValueToProperty(
                                    new \ReflectionClass($associatedJoin[0]['model']),
                                    $property,
                                    $columnValue
                                );
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                        $associatedModels[$model] = $associatedModel;
                    }

                    if (count($associatedModels)) {
                        foreach ($this->queryBuilder->getJoins() as $join) {
                            $association = $associatedModels[$join['alias']] ?? null;
                            $joinToAlias = null;
                            $joinToColumn = null;

                            foreach ($join['conditions'] as $condition) {
                                $conditionParts = [
                                    'alias' => [],
                                    'column' => [],
                                ];
                                foreach (explode(' ', $condition) as $conditionPart) {
                                    if (!str_contains($conditionPart, '.')) {
                                        continue;
                                    }

                                    $conditionParts['alias'][] = explode('.', $conditionPart)[0];
                                    $conditionParts['column'][] = explode('.', $conditionPart)[1];
                                }

                                $referencesMainClass = in_array($this->queryBuilder->getAlias(), $conditionParts['alias']);

                                if ($referencesMainClass) {
                                    $joinToAlias = $conditionParts['alias'][array_search($this->queryBuilder->getAlias(), $conditionParts['alias'])];
                                    $joinToColumn = $conditionParts['column'][array_search($this->queryBuilder->getAlias(), $conditionParts['alias'])];
                                } else {
                                    for ($i = 0; $i < count($conditionParts['alias']); $i++) {
                                        if ($conditionParts['alias'][$i] !== $join['alias']) {
                                            $joinToAlias = $conditionParts['alias'][$i];
                                            $joinToColumn = $conditionParts['column'][$i];
                                        }
                                    }
                                }
                            }

                            if ($joinToAlias) {
                                if ($joinToAlias === $this->queryBuilder->getAlias()) {
                                    $modelInstance->$joinToColumn = $association;
                                } else {
                                    $joinToModel = $associatedModels[$joinToAlias] ?? null;

                                    if ($joinToModel) {
                                        $joinToModel->$joinToColumn = $association;
                                    }
                                }
                            }
                        }
                    }

                    $output[] = $modelInstance;
                }
                return $output;
            }
        } catch (\Exception $exception) {
            return [];
        }

        return [];
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

    private function mapCallingModel(array $modelData, LoomModel $modelInstance): LoomModel
    {
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

    private function mapToModel(string $alias, array $modelData, LoomModel $modelInstance): LoomModel
    {
        if ($alias === $this->queryBuilder->getAlias()) {
            return $this->mapCallingModel($modelData, $modelInstance);
        }

        return $modelInstance;
    }

    /**
     * @throws \DateMalformedStringException|\Exception|\ReflectionException
     */
    private function convertDatabaseValueToProperty(\ReflectionClass $class, string $property, string|int $value): \DateTime|bool|int|string
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