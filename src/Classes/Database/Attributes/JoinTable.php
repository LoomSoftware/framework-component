<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class JoinTable
{
    public function __construct(private string $name, private string $schema, private string $joinAlias, private string $selfColumn, private string $foreignColumn)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchema(): string
    {
        return $this->schema;
    }

    public function getJoinAlias(): string
    {
        return $this->joinAlias;
    }

    public function getSelfColumn(): string
    {
        return $this->selfColumn;
    }

    public function getForeignColumn(): string
    {
        return $this->foreignColumn;
    }
}