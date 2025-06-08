<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Database\Mapper;

use Loom\FrameworkComponent\Classes\Database\Attributes\Column;

class PropertyColumnMapper
{
    /**
     * @throws \ReflectionException
     */
    public static function map(string $className): array
    {
        $properties = [];
        $reflectionClass = new \ReflectionClass($className);

        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);

            if ($attributes) {
                $properties[$property->getName()] = $attributes[0]->getArguments()['name'];
            }
        }

        return $properties;
    }
}