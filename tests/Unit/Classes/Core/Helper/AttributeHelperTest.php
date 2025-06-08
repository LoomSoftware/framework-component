<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Tests\Classes\Core\Helper;

use Loom\FrameworkComponent\Classes\Core\Helper\AttributeHelper;
use Loom\FrameworkComponent\Classes\Database\Attributes\Column;
use Loom\FrameworkComponent\Classes\Database\Attributes\Schema;
use Loom\FrameworkComponent\Classes\Database\Attributes\Table;
use Loom\FrameworkComponent\TestData\Database\Model\Package;
use Loom\FrameworkComponent\TestData\Database\Model\PackageType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AttributeHelperTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    #[DataProvider('attributeTestDataProvider')]
    public function testGetAttributeValue(string $className, string $attributeName, string $argumentName, ?string $expected): void
    {
        self::assertEquals($expected, AttributeHelper::getAttributeValue($className, $attributeName, $argumentName));
    }

    public static function attributeTestDataProvider(): array
    {
        return [
            [
                'className' => PackageType::class,
                'attributeName' => Schema::class,
                'argumentName' => 'name',
                'expected' => 'Application',
            ],
            [
                'className' => Package::class,
                'attributeName' => Table::class,
                'argumentName' => 'name',
                'expected' => 'tblPackage',
            ],
            [
                'className' => Package::class,
                'attributeName' => Column::class,
                'argumentName' => 'name',
                'expected' => null,
            ],
        ];
    }
}