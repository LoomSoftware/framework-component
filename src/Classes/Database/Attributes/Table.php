<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Table
{
    public function __construct(private readonly string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}