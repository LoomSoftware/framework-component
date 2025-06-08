<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Core\Helper;

class AttributeHelper
{
    /**
     * @throws \ReflectionException
     */
    public static function getAttributeValue(string $className, string $attributeName, string $argument): mixed
    {
        $attributes = AttributeHelper::getClassAttributes($className);

        foreach ($attributes as $attribute) {
            if ($attribute->name === $attributeName) {
                $arguments = $attribute->getArguments();

                if (array_key_exists($argument, $arguments)) {
                    return $arguments[$argument];
                }
            }
        }

        return null;
    }

    /**
     * @throws \ReflectionException
     */
    private static function getClassAttributes(string $className): array
    {
        return new \ReflectionClass($className)->getAttributes();
    }
}