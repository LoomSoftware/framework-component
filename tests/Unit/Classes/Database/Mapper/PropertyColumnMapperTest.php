<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Tests\Classes\Database\Mapper;

use Loom\FrameworkComponent\TestData\Database\Model\Model;
use Loom\FrameworkComponent\TestData\Database\Model\Package;
use Loom\FrameworkComponent\Classes\Database\Mapper\PropertyColumnMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PropertyColumnMapperTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    #[DataProvider('mapDataProvider')]
    public function testMap(string $class, array $expected): void
    {
        self::assertEquals($expected, PropertyColumnMapper::map($class));
    }

    public static function mapDataProvider(): array
    {
        return [
            Model::class => [
                'class' => Model::class,
                'expected' => [
                    'id' => 'intModelId',
                ],
            ],
            Package::class => [
                'class' => Package::class,
                'expected' => [
                    'id' => 'intPackageId',
                    'name' => 'strPackageName',
                    'packageType' => 'intPackageTypeId',
                ],
            ],
        ];
    }
}