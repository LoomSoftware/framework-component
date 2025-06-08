<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database\Query;

use Loom\FrameworkComponent\Classes\Database\LoomModel;
use Loom\FrameworkComponent\Classes\Database\Mapper\PropertyColumnMapper;

class QueryBuilder
{
    private string $schema;
    private string $table;
    private array $selects = [];
    private array $innerJoins = [];
    private array $wheres = [];

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

    public function select(array $columns = ['*']): static
    {
        $this->selects = $columns;

        return $this;
    }

    public function innerJoin(string $model, string $alias, array $conditions): static
    {
        $this->innerJoins[] = [
            'model' => $model,
            'alias' => $alias,
            'conditions' => $conditions,
        ];

        return $this;
    }

    public function where(string $columnOrProperty, string $value): static
    {
        $this->wheres[] = [$columnOrProperty, $value];

        return $this;
    }

    /**
     * @throws \ReflectionException
     */
    public function getQueryString(): string
    {
        $queryString = $this->getSelectQueryStringPartial();
        $queryString .= $this->getFromQueryStringPartial();
        $queryString .= $this->getInnerJoinQueryStringPartial();
        $queryString .= $this->getWhereQueryStringPartial();

        var_dump($queryString);

        return $queryString;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getJoins(): array
    {
        return $this->innerJoins;
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

            foreach ($this->innerJoins as $join) {
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

            foreach ($this->innerJoins as $join) {
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
        return sprintf(
            'FROM %s.%s %s',
            $this->schema,
            $this->table,
            $this->alias
        );
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

            $conditions = array_map(function ($condition) {
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

                    foreach ($this->innerJoins as $innerJoin) {
                        if ($alias === $innerJoin['alias']) {
                            $propertyColumnMap = PropertyColumnMapper::map($innerJoin['model']);

                            if (isset($propertyColumnMap[$column])) {
                                $finalCondition .= sprintf('%s.%s', $innerJoin['alias'], $propertyColumnMap[$column]);
                            }

                            if (in_array($column, array_values($propertyColumnMap))) {
                                $finalCondition .= sprintf('%s.%s', $innerJoin['alias'], $column);
                            }
                        }
                    }
                }

                return $finalCondition;
            }, $join['conditions']);

            $sql .= sprintf(
                ' INNER JOIN %s.%s %s ON %s',
                $model::getSchemaName(),
                $model::getTableName(),
                $join['alias'],
                implode(' AND ', $conditions)
            );
        }

        return $sql;
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
                        if (is_string($value)) {
                            $whereStrings[] = sprintf('%s.%s = \'%s\'', $this->alias, $propertyColumnMap[$column], $value);
                        } else {
                            $whereStrings[] = sprintf('%s.%s = %s', $this->alias, $propertyColumnMap[$column], $value);
                        }
                    }

                    if (in_array($column, array_values($propertyColumnMap))) {
                        if (is_string($value)) {
                            $whereStrings[] = sprintf('%s.%s = \'%s\'', $this->alias, $column, $value);
                        } else {
                            $whereStrings[] = sprintf('%s.%s = %s', $this->alias, $column, $value);
                        }
                    }
                }
            }
        }

        $ret = count($whereStrings)
            ? sprintf(' WHERE %s', implode(' AND ', $whereStrings))
            : '';

        var_dump($ret);
        return $ret;
    }
}