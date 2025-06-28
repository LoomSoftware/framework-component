<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database;

use Loom\FrameworkComponent\Classes\Core\Helper\AttributeHelper;
use Loom\FrameworkComponent\Classes\Core\Utility\ModelCollection;
use Loom\FrameworkComponent\Classes\Database\Attributes\Column;
use Loom\FrameworkComponent\Classes\Database\Attributes\ID;
use Loom\FrameworkComponent\Classes\Database\Attributes\JoinTable;
use Loom\FrameworkComponent\Classes\Database\Attributes\Schema;
use Loom\FrameworkComponent\Classes\Database\Attributes\Table;
use Loom\FrameworkComponent\Classes\Database\Mapper\PropertyColumnMapper;
use Loom\FrameworkComponent\Classes\Database\Query\QueryBuilder;

abstract class LoomModel
{
    protected static ?DatabaseConnection $databaseConnection = null;
    protected ?QueryBuilder $queryBuilder = null;

    public function getQueryBuilder(): ?QueryBuilder
    {
        return $this->queryBuilder;
    }

    public static function setDatabaseConnection(DatabaseConnection $databaseConnection): void
    {
        static::$databaseConnection = $databaseConnection;
    }

    public static function create(array $data): static
    {
        $instance = new static();

        $propertyColumnMap = PropertyColumnMapper::map(static::class);

        foreach ($data as $dataProperty => $value) {
            if (isset($propertyColumnMap[$dataProperty])) {
                $instance->$dataProperty = $value;
            }

            if (in_array($dataProperty, array_values($propertyColumnMap))) {
                foreach ($propertyColumnMap as $property => $column) {
                    if ($column === $property) {
                        $instance->$property = $value;
                    }
                }
            }
        }

        return $instance;
    }

    public function save(): static
    {
        try {
            $identifierProperty = $this->getIdentifier();
        } catch (\Exception $exception) {
            return $this;
        }

        if (!$this->$identifierProperty) {
            $this->queryBuilder = new QueryBuilder(static::class, 't0');

            $this->queryBuilder->insert($this);

            $query = static::$databaseConnection?->getConnection()->prepare($this->queryBuilder->getQueryString());

            $query->execute($this->queryBuilder->getParameters());
            $this->$identifierProperty = (int) static::$databaseConnection?->getConnection()->lastInsertId();
        } else {
            $this->queryBuilder = new QueryBuilder(static::class, 't0');

            $this->queryBuilder->update($this);

            $query = static::$databaseConnection?->getConnection()->prepare($this->queryBuilder->getQueryString());
            $query->execute($this->queryBuilder->getParameters());
        }

        return $this;
    }

    public static function select(array $columns = ['*'], string $alias = 't0'): static
    {
        $instance = new static();

        try {
            $instance->queryBuilder = new QueryBuilder(static::class, $alias)->select($columns);

            return $instance;
        } catch (\Exception $exception) {
            return $instance;
        }
    }

    public function innerJoin(string $class, string $alias, array $conditions = []): static
    {
        if (!$this->queryBuilder) {
            return $this;
        }

        $this->queryBuilder->innerJoin($class, $alias, $conditions);

        return $this;
    }

    public function leftJoin(string $class, string $alias, array $conditions): static
    {
        if (!$this->queryBuilder) {
            return $this;
        }

        $this->queryBuilder->leftJoin($class, $alias, $conditions);

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

    public function whereNot(string $columnOrProperty, mixed $value): static
    {
        if (!$this->queryBuilder) {
            return $this;
        }

        $this->queryBuilder->whereNot($columnOrProperty, $value);

        return $this;
    }

    public function whereIn(string $columnOrProperty, array $values): static
    {
        if (!$this->queryBuilder) {
            return $this;
        }

        $this->queryBuilder->whereIn($columnOrProperty, $values);

        return $this;
    }

    public function whereNotIn(string $columnOrProperty, array $values): static
    {
        if (!$this->queryBuilder) {
            return $this;
        }

        $this->queryBuilder->whereNotIn($columnOrProperty, $values);

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

    public function limit(int $limit): static
    {
        if (!$this->queryBuilder) {
            return $this;
        }

        $this->queryBuilder->limit($limit);

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

                            if (empty($join['conditions'])) {
                                $selfReflectionClass = new \ReflectionClass(static::class);

                                foreach($selfReflectionClass->getProperties() as $property) {
                                    if (empty($property->getAttributes(JoinTable::class))) {
                                        continue;
                                    }

                                    $joinTableConditions = $this->queryBuilder->getJoinTableConditions();

                                    foreach ($joinTableConditions as $joinTableCondition) {
                                        if ($joinTableCondition['property'] !== $property->getName()) {
                                            continue;
                                        }

                                        $joinToProperty = $joinTableCondition['property'];

                                        if (isset($modelInstance->$joinToProperty) && $modelInstance->$joinToProperty instanceof ModelCollection) {
                                            $modelInstance->$joinToProperty->add($association);
                                        } else {
                                            $modelInstance->$joinToProperty = new ModelCollection();
                                            $modelInstance->$joinToProperty->add($association);
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
        } catch (\Exception $exception) {
            return [];
        }

        return [];
    }

    public function getOne(): ?static
    {
        return $this->get()[0] ?? null;
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

    /**
     * @throws \Exception
     */
    public function getIdentifier(): string
    {
        $reflectionClass = new \ReflectionClass(static::class);

        foreach ($reflectionClass->getProperties() as $property) {
            if (empty($property->getAttributes(ID::class))) {
                continue;
            }

            return $property->getName();
        }

        throw new \Exception('No identifier found');
    }

    public function getIdentifierColumn(): string
    {
        $reflectionClass = new \ReflectionClass(static::class);

        foreach ($reflectionClass->getProperties() as $property) {
            if (empty($property->getAttributes(ID::class))) {
                continue;
            }

            $columnAttribute = $property->getAttributes(Column::class);

            if (empty($columnAttribute)) {
                continue;
            }

            return $columnAttribute[0]->getArguments()['name'];
        }

        return '';
    }

    /**
     * @throws \Exception
     */
    public function getIdentifierValue(): int
    {
        return $this->{$this->getIdentifier()};
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
    private function convertDatabaseValueToProperty(\ReflectionClass $class, string $property, string|int|null $value): \DateTime|bool|int|string|null
    {
        $reflectionProperty = $class->getProperty($property);

        if (is_subclass_of($reflectionProperty->getType()->getName(), LoomModel::class)) {
            throw new \Exception('Skip');
        }

        if ($reflectionProperty->getType()->getName() === \DateTimeInterface::class) {
            $value = $value ? new \DateTime($value) : $value;
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