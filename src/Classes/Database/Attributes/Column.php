<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(private readonly string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}