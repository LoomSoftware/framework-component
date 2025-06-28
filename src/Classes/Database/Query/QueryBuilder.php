<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database\Query;

use Loom\FrameworkComponent\Classes\Database\Attributes\Column;
use Loom\FrameworkComponent\Classes\Database\Attributes\ID;
use Loom\FrameworkComponent\Classes\Database\Attributes\JoinTable;
use Loom\FrameworkComponent\Classes\Database\LoomModel;
use Loom\FrameworkComponent\Classes\Database\Mapper\PropertyColumnMapper;

class QueryBuilder
{
    private string $schema;
    private string $table;
    private array $selects = [];
    private array $innerJoins = [];
    private array $leftJoins = [];
    private array $wheres = [];
    private array $whereNots = [];
    private array $whereIns = [];
    private array $whereNotIns = [];
    private array $parameters = [];
    private array $orderBys = [];
    private ?int $limit = null;
    private array $joinTableConditions = [];
    private ?LoomModel $insert = null;
    private ?LoomModel $update = null;

    /**
     * @throws \Exception
     */
    public function __construct(private readonly string $model, private readonly string $alias)
    {
        if (!class_exists($this->model)) {
            throw new \Exception('Model class does not exist');
        }

        if (!is_subclass_of($this->model, LoomModel::class)) {
            throw new \Exception('Model class is not a LoomModel');
        }

        $this->schema = $this->model::getSchemaName();
        $this->table = $this->model::getTableName();
    }

    public function reset(): static
    {
        $this->selects = [];
        $this->innerJoins = [];
        $this->leftJoins = [];
        $this->wheres = [];
        $this->whereNots = [];
        $this->whereIns = [];
        $this->whereNotIns = [];
        $this->parameters = [];
        $this->orderBys = [];
        $this->limit = null;
        $this->insert = null;

        return $this;
    }

    public function select(array $columns = ['*']): static
    {
        $this->selects = $columns;

        return $this;
    }

    public function innerJoin(string $model, string $alias, array $conditions = []): static
    {
        $this->innerJoins[] = [
            'model' => $model,
            'alias' => $alias,
            'conditions' => $conditions,
        ];

        return $this;
    }

    public function leftJoin(string $model, string $alias, array $conditions): static
    {
        $this->leftJoins[] = [
            'model' => $model,
            'alias' => $alias,
            'conditions' => $conditions,
        ];

        return $this;
    }

    public function where(string $columnOrProperty, mixed $value): static
    {
        $this->wheres[] = [$columnOrProperty, $value];

        return $this;
    }

    public function whereNot(string $columnOrProperty, mixed $value): static
    {
        $this->whereNots[] = [$columnOrProperty, $value];

        return $this;
    }

    public function whereIn(string $columnOrProperty, array $values): static
    {
        $this->whereIns[] = [$columnOrProperty, $values];

        return $this;
    }

    public function whereNotIn(string $columnOrProperty, array $values): static
    {
        $this->whereNotIns[] = [$columnOrProperty, $values];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderBys[] = [$column, $direction];

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function insert(LoomModel $model): static
    {
        $this->insert = $model;

        return $this;
    }

    public function update(LoomModel $model): static
    {
        $this->update = $model;

        return $this;
    }

    public function getQueryString(): string
    {
        $queryString = '';

        try {
            if ($this->insert) {
                $queryString = $this->getInsertQueryStringPartial();
            } elseif ($this->update) {
                $queryString = $this->getUpdateQueryStringPartial();
            } else {
                $queryString = $this->getSelectQueryStringPartial();
                $queryString .= $this->getFromQueryStringPartial();
                $queryString .= $this->getInnerJoinQueryStringPartial();
                $queryString .= $this->getLeftJoinQueryStringPartial();
                $queryString .= $this->getWhereQueryStringPartial();
                $queryString .= $this->getOrderByQueryStringPartial();
                $queryString .= $this->getLimitQueryStringPartial();
            }

            return $queryString;
        } catch (\Exception $exception) {
            return $queryString;
        }
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getJoins(): array
    {
        return $this->innerJoins;
    }

    public function getJoinTableConditions(): array
    {
        return $this->joinTableConditions;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @throws \ReflectionException
     */
    private function getSelectQueryStringPartial(): string
    {
        $joinSelects = null;

        if (empty($this->selects) || (count($this->selects) === 1 && $this->selects[0] === '*')) {
            $this->selects = ['*'];
            $joinSelects = [];

            foreach (array_merge($this->innerJoins, $this->leftJoins) as $join) {
                $propertyColumnMap = PropertyColumnMapper::map($join['model']);

                foreach ($propertyColumnMap as $property => $column) {
                    $joinSelects[] = sprintf('%s.%s AS %s_%s', $join['alias'], $column, $join['alias'], $property);
                }
            }
        }

        $propertyColumnMap = PropertyColumnMapper::map($this->model);
        $selects = array_map(function ($select) use ($propertyColumnMap) {
            if (isset($propertyColumnMap[$select])) {
                return sprintf('%s.%s AS %s_%s', $this->alias, $propertyColumnMap[$select], $this->alias, $select);
            }

            if (in_array($select, array_values($propertyColumnMap))) {
                foreach ($propertyColumnMap as $property => $column) {
                    if ($column === $select) {
                        return sprintf('%s.%s AS %s_%s', $this->alias, $column, $this->alias, $property);
                    }
                }
            }

            if (str_contains($select, '.')) {
                $alias = explode('.', $select)[0];
                $column = explode('.', $select)[1];

                if ($alias === $this->alias) {
                    if (isset($propertyColumnMap[$column])) {
                        return sprintf('%s.%s AS %s_%s', $alias, $propertyColumnMap[$column], $alias, $column);
                    }
                }
            }

            if ($select === $this->alias || $select === '*') {
                $columnSelects = [];

                foreach ($propertyColumnMap as $property => $column) {
                    $columnSelects[] = sprintf('%s.%s AS %s_%s', $this->alias, $column, $this->alias, $property);
                }

                return implode(', ', $columnSelects);
            }

            foreach (array_merge($this->innerJoins, $this->leftJoins) as $join) {
                if ($select === $join['alias']) {
                    $propertyColumnMap = PropertyColumnMapper::map($join['model']);

                    $columnSelects = [];

                    foreach ($propertyColumnMap as $property => $column) {
                        $columnSelects[] = sprintf('%s.%s AS %s_%s', $join['alias'], $column, $join['alias'], $property);
                    }

                    return implode(', ', $columnSelects);
                }
            }

            return $select;
        }, $this->selects);

        if ($joinSelects) {
            $selects = array_merge($selects, $joinSelects);
        }

        return sprintf('SELECT %s ', implode(', ', $selects));
    }

    private function getFromQueryStringPartial(): string
    {
        return sprintf('FROM %s.%s %s', $this->schema, $this->table, $this->alias);
    }

    /**
     * @throws \ReflectionException
     */
    private function getInnerJoinQueryStringPartial(): string
    {
        $sql = '';

        foreach ($this->innerJoins as $join) {
            $model = $join['model'];

            if (!is_subclass_of($model, LoomModel::class)) {
                continue;
            }

            if (!empty($join['conditions'])) {
                $sql .= sprintf(
                    ' INNER JOIN %s.%s %s ON %s',
                    $model::getSchemaName(),
                    $model::getTableName(),
                    $join['alias'],
                    implode(' AND ', $this->parseJoinConditions($join['conditions']))
                );
            } else {
                $reflectionClass = new \ReflectionClass($this->model);

                foreach ($reflectionClass->getProperties() as $property) {
                    $joinTableAttribute = $property->getAttributes(JoinTable::class);

                    if (empty($joinTableAttribute)) {
                        continue;
                    }

                    $joinTableAttribute = $joinTableAttribute[0]->newInstance();

                    $sql .= sprintf(
                        ' INNER JOIN %s.%s %s ON %s.%s = %s.%s',
                        $joinTableAttribute->getSchema(),
                        $joinTableAttribute->getName(),
                        $joinTableAttribute->getJoinAlias(),
                        $this->alias,
                        $joinTableAttribute->getSelfColumn(),
                        $joinTableAttribute->getJoinAlias(),
                        $joinTableAttribute->getSelfColumn()
                    );

                    $sql .= sprintf(
                        ' INNER JOIN %s.%s %s ON %s.%s = %s.%s',
                        $model::getSchemaName(),
                        $model::getTableName(),
                        $join['alias'],
                        $joinTableAttribute->getJoinAlias(),
                        $joinTableAttribute->getForeignColumn(),
                        $join['alias'],
                        $joinTableAttribute->getForeignColumn()
                    );
                    $this->joinTableConditions[] = [
                        'property' => $property->getName(),
                        'alias' => $join['alias'],
                    ];
                }
            }
        }

        return $sql;
    }

    /**
     * @throws \ReflectionException
     */
    private function getLeftJoinQueryStringPartial(): string
    {
        $sql = '';

        foreach ($this->leftJoins as $join) {
            $model = $join['model'];

            if (!is_subclass_of($model, LoomModel::class)) {
                continue;
            }

            $sql .= sprintf(
                ' LEFT JOIN %s.%s %s ON %s',
                $model::getSchemaName(),
                $model::getTableName(),
                $join['alias'],
                implode(' AND ', $this->parseJoinConditions($join['conditions']))
            );
        }

        return $sql;
    }

    private function parseJoinConditions(array $conditions): array
    {
        return array_map(function ($condition) {
            $splitCondition = explode(' ', $condition);
            $finalCondition = '';

            foreach ($splitCondition as $conditionPart) {
                if (!str_contains($conditionPart, '.')) {
                    $finalCondition .= sprintf(' %s ', $conditionPart);
                    continue;
                }

                $alias = explode('.', $conditionPart)[0];
                $column = explode('.', $conditionPart)[1];

                if ($alias === $this->alias) {
                    $propertyColumnMap = PropertyColumnMapper::map($this->model);

                    if (isset($propertyColumnMap[$column])) {
                        $finalCondition .= sprintf('%s.%s', $this->alias, $propertyColumnMap[$column]);
                    }

                    if (in_array($column, array_values($propertyColumnMap))) {
                        $finalCondition .= sprintf('%s.%s', $this->alias, $column);
                    }
                }

                foreach (array_merge($this->innerJoins, $this->leftJoins) as $join) {
                    if ($alias === $join['alias']) {
                        $propertyColumnMap = PropertyColumnMapper::map($join['model']);

                        if (isset($propertyColumnMap[$column])) {
                            $finalCondition .= sprintf('%s.%s', $join['alias'], $propertyColumnMap[$column]);
                        }

                        if (in_array($column, array_values($propertyColumnMap))) {
                            $finalCondition .= sprintf('%s.%s', $join['alias'], $column);
                        }
                    }
                }
            }

            return $finalCondition;
        }, $conditions);
    }

    private function getWhereQueryStringPartial(): string
    {
        $whereStrings = [];

        foreach ($this->wheres as $where) {
            $columnOrProperty = $where[0];
            $value = $where[1];

            if (str_contains($columnOrProperty, '.')) {
                $alias = explode('.', $columnOrProperty)[0];
                $column = explode('.', $columnOrProperty)[1];

                if ($alias === $this->alias) {
                    $propertyColumnMap = PropertyColumnMapper::map($this->model);

                    if (isset($propertyColumnMap[$column])) {
                        $whereStrings[] = $this->addWhereString($this->alias, $propertyColumnMap[$column]);
                    }

                    if (in_array($column, array_values($propertyColumnMap))) {
                        $whereStrings[] = $this->addWhereString($this->alias, $column);
                    }
                }

                foreach ($this->innerJoins as $join) {
                    if ($alias === $join['alias']) {
                        $propertyColumnMap = PropertyColumnMapper::map($join['model']);

                        if (isset($propertyColumnMap[$column])) {
                            $whereStrings[] = $this->addWhereString($join['alias'], $propertyColumnMap[$column]);
                        }

                        if (in_array($column, array_values($propertyColumnMap))) {
                            $whereStrings[] = $this->addWhereString($join['alias'], $column);
                        }
                    }
                }

                $this->parameters[] = $value;
            }
        }

        foreach ($this->whereNots as $where) {
            $columnOrProperty = $where[0];
            $value = $where[1];

            if (str_contains($columnOrProperty, '.')) {
                $alias = explode('.', $columnOrProperty)[0];
                $column = explode('.', $columnOrProperty)[1];

                if ($alias === $this->alias) {
                    $propertyColumnMap = PropertyColumnMapper::map($this->model);

                    if (isset($propertyColumnMap[$column])) {
                        $whereStrings[] = sprintf('%s.%s != ?', $this->alias, $propertyColumnMap[$column]);
                    }

                    if (in_array($column, array_values($propertyColumnMap))) {
                        $whereStrings[] = sprintf('%s.%s != ?', $this->alias, $column);
                    }
                }

                foreach ($this->innerJoins as $join) {
                    if ($alias === $join['alias']) {
                        $propertyColumnMap = PropertyColumnMapper::map($join['model']);

                        if (isset($propertyColumnMap[$column])) {
                            $whereStrings[] = sprintf('%s.%s != ?', $join['alias'], $propertyColumnMap[$column]);
                        }

                        if (in_array($column, array_values($propertyColumnMap))) {
                            $whereStrings[] = sprintf('%s.%s != ?', $join['alias'], $column);
                        }
                    }
                }

                $this->parameters[] = $value;
            }
        }

        foreach ($this->whereIns as $where) {
            $columnOrProperty = $where[0];
            $values = $where[1];

            if (str_contains($columnOrProperty, '.')) {
                $alias = explode('.', $columnOrProperty)[0];
                $column = explode('.', $columnOrProperty)[1];

                if ($alias === $this->alias) {
                    $propertyColumnMap = PropertyColumnMapper::map($this->model);

                    if (isset($propertyColumnMap[$column])) {
                        $whereStrings[] = sprintf('%s.%s IN (%s)', $this->alias, $propertyColumnMap[$column], implode(', ', array_fill(0, count($values), '?')));
                    }

                    if (in_array($column, array_values($propertyColumnMap))) {
                        $whereStrings[] = sprintf('%s.%s IN (%s)', $this->alias, $propertyColumnMap[$column], implode(', ', array_fill(0, count($values), '?')));
                    }
                }

                foreach ($this->innerJoins as $join) {
                    if ($alias === $join['alias']) {
                        $propertyColumnMap = PropertyColumnMapper::map($join['model']);

                        if (isset($propertyColumnMap[$column])) {
                            $whereStrings[] = sprintf('%s.%s IN (%s)', $join['alias'], $propertyColumnMap[$column], implode(', ', array_fill(0, count($values), '?')));
                        }

                        if (in_array($column, array_values($propertyColumnMap))) {
                            $whereStrings[] = sprintf('%s.%s IN (%s)', $join['alias'], $column, implode(', ', array_fill(0, count($values), '?')));
                        }
                    }
                }

                foreach ($values as $value) {
                    $this->parameters[] = $value;
                }
            }
        }

        foreach ($this->whereNotIns as $where) {
            $columnOrProperty = $where[0];
            $values = $where[1];

            if (str_contains($columnOrProperty, '.')) {
                $alias = explode('.', $columnOrProperty)[0];
                $column = explode('.', $columnOrProperty)[1];

                if ($alias === $this->alias) {
                    $propertyColumnMap = PropertyColumnMapper::map($this->model);

                    if (isset($propertyColumnMap[$column])) {
                        $whereStrings[] = sprintf('%s.%s NOT IN (%s)', $this->alias, $propertyColumnMap[$column], implode(', ', array_fill(0, count($values), '?')));
                    }

                    if (in_array($column, array_values($propertyColumnMap))) {
                        $whereStrings[] = sprintf('%s.%s NOT IN (%s)', $this->alias, $column, implode(', ', array_fill(0, count($values), '?')));
                    }
                }

                foreach ($this->innerJoins as $join) {
                    if ($alias === $join['alias']) {
                        $propertyColumnMap = PropertyColumnMapper::map($join['model']);

                        if (isset($propertyColumnMap[$column])) {
                            $whereStrings[] = sprintf('%s.%s NOT IN (%s)', $join['alias'], $propertyColumnMap[$column], implode(', ', array_fill(0, count($values), '?')));
                        }

                        if (in_array($column, array_values($propertyColumnMap))) {
                            $whereStrings[] = sprintf('%s.%s NOT IN (%s)', $join['alias'], $column, implode(', ', array_fill(0, count($values), '?')));
                        }
                    }
                }

                foreach ($values as $value) {
                    $this->parameters[] = $value;
                }
            }
        }

        return count($whereStrings)
            ? sprintf(' WHERE %s', implode(' AND ', $whereStrings))
            : '';
    }

    private function getOrderByQueryStringPartial(): string
    {
        $orderByStrings = [];

        foreach ($this->orderBys as $orderBy) {
            $column = $orderBy[0];
            $direction = $orderBy[1];

            if (str_contains($column, '.')) {
                $alias = explode('.', $column)[0];
                $columnName = explode('.', $column)[1];

                if ($alias === $this->alias) {
                    $propertyColumnMap = PropertyColumnMapper::map($this->model);

                    if (isset($propertyColumnMap[$columnName])) {
                        $orderByStrings[] = sprintf('%s.%s %s', $this->alias, $propertyColumnMap[$columnName], $direction);
                    }

                    if (in_array($columnName, array_values($propertyColumnMap))) {
                        $orderByStrings[] = sprintf('%s.%s %s', $this->alias, $columnName, $direction);
                    }
                } else {
                    foreach ($this->innerJoins as $join) {
                        if ($alias === $join['alias']) {
                            $propertyColumnMap = PropertyColumnMapper::map($join['model']);

                            if (isset($propertyColumnMap[$columnName])) {
                                $orderByStrings[] = sprintf('%s.%s %s', $join['alias'], $propertyColumnMap[$columnName], $direction);
                            }

                            if (in_array($columnName, array_values($propertyColumnMap))) {
                                $orderByStrings[] = sprintf('%s.%s %s', $join['alias'], $columnName, $direction);
                            }
                        }
                    }
                }
            }
        }

        return count($orderByStrings)
            ? sprintf(' ORDER BY %s', implode(', ', $orderByStrings))
            : '';
    }

    private function getInsertQueryStringPartial(): string
    {
        $columns = [];
        $reflectionClass = new \ReflectionClass($this->insert);

        foreach ($reflectionClass->getProperties() as $property) {
            $columnAttribute = $property->getAttributes(Column::class);
            $identifierAttribute = $property->getAttributes(ID::class);

            if ($columnAttribute && !$identifierAttribute) {
                $propertyValue = $property->getValue($this->insert);

                if ($propertyValue instanceof LoomModel) {
                    $identifierProperty = $propertyValue->getIdentifier();

                    $associationReflectionClass = new \ReflectionClass($propertyValue);
                    $associationProperty = $associationReflectionClass->getProperty($identifierProperty);

                    if ($associationProperty->getValue($propertyValue)) {
                        $this->parameters[] = $associationProperty->getValue($propertyValue);
                    } else {
                        $newModel = $propertyValue->save();

                        $associationReflectionClass = new \ReflectionClass($newModel);
                        $associationProperty = $associationReflectionClass->getProperty($identifierProperty);
                        $this->parameters[] = $associationProperty->getValue($newModel);
                    }
                } elseif (is_string($propertyValue) || is_numeric($propertyValue)) {
                    $this->parameters[] = $propertyValue;
                } elseif ($propertyValue instanceof \DateTimeInterface) {
                    $this->parameters[] = $propertyValue->format('Y-m-d H:i:s');
                } elseif (is_bool($propertyValue)) {
                    $this->parameters[] = $propertyValue ? 1 : 0;
                } else {
                    $this->parameters[] = $propertyValue;
                }

                foreach ($columnAttribute[0]->getArguments() as $argument) {
                    $columns[] = $argument;
                }
            }
        }

        $columnsString = implode(',', $columns);
        $valuesString = implode(',', array_fill(0, count($columns), '?'));

        return sprintf('INSERT INTO %s.%s (%s) VALUES (%s)', $this->schema, $this->table, $columnsString, $valuesString);
    }

    private function getUpdateQueryStringPartial(): string
    {
        $sql = sprintf('UPDATE %s.%s SET ', $this->schema, $this->table);
        $updates = [];

        $reflectionClass = new \ReflectionClass($this->update);

        foreach ($reflectionClass->getProperties() as $property) {
            $columnAttribute = $property->getAttributes(Column::class);
            $identifierAttribute = $property->getAttributes(ID::class);

            if (empty($columnAttribute) || $identifierAttribute) {
                continue;
            }

            $propertyValue = $property->getValue($this->update);
            $propertyColumnMap = PropertyColumnMapper::map($this->update::class);

            if ($propertyValue instanceof LoomModel) {
                $identifierProperty = $propertyValue->getIdentifier();
                $associationReflectionClass = new \ReflectionClass($propertyValue);
                $associationProperty = $associationReflectionClass->getProperty($identifierProperty);

                $this->parameters[] = $associationProperty->getValue($propertyValue);
                $updates[] = sprintf('%s = ?', $propertyColumnMap[$property->getName()]);
            } elseif (is_string($propertyValue) || is_numeric($propertyValue)) {
                $this->parameters[] = $propertyValue;
                $updates[] = sprintf('%s = ?', $propertyColumnMap[$property->getName()]);
            } elseif ($propertyValue instanceof \DateTimeInterface) {
                $this->parameters[] = sprintf('\'%s\'', $propertyValue->format('Y-m-d H:i:s'));
                $updates[] = sprintf('%s = ?', $propertyColumnMap[$property->getName()]);
            } elseif (is_bool($propertyValue)) {
                $this->parameters[] = $propertyValue ? 1 : 0;
                $updates[] = sprintf('%s = ?', $propertyColumnMap[$property->getName()]);
            }
        }

        $sql .= implode(', ', $updates);
        $identifierColumn = $this->update->getIdentifierColumn();
        $identifierProperty = $this->update->getIdentifier();

        $this->parameters[] = $reflectionClass->getProperty($identifierProperty)->getValue($this->update);

        return sprintf('%s WHERE %s = ?', $sql, $identifierColumn);
    }

    private function getLimitQueryStringPartial(): string
    {
        return $this->limit
            ? sprintf(' LIMIT %d', $this->limit)
            : '';
    }

    private function addWhereString(string $alias, string $column): string
    {
        return sprintf('%s.%s = ?', $alias, $column);
    }
}