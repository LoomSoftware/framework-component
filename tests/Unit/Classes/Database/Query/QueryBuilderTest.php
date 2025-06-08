<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Tests\Classes\Database\Query;

use Data\Database\Model\StandardModel;
use Loom\FrameworkComponent\Classes\Database\DatabaseConnection;
use Loom\FrameworkComponent\Classes\Database\Query\QueryBuilder;
use Loom\FrameworkComponent\TestData\Database\Model\Package;
use Loom\FrameworkComponent\TestData\Database\Model\PackageType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    public function testNonExistentModelClass(): void
    {
        $this->expectException(\Exception::class);

        new QueryBuilder('invalid', 'm');
    }

    public function testInvalidModelClass(): void
    {
        $this->expectException(\Exception::class);

        new QueryBuilder(DatabaseConnection::class, 'dbc');
    }

    /**
     * @throws \ReflectionException
     */
    #[DataProvider('queryBuilderDataProvider')]
    public function testGetQueryString(QueryBuilder $queryBuilder, string $expected): void
    {
        self::assertEquals($expected, $queryBuilder->getQueryString());
    }

    public function testSelectAndInnerJoinAreCovered(): void
    {
        $qb = new QueryBuilder(Package::class, 'p');
        $result = $qb->select(['id', 'name']);
        $this->assertSame($qb, $result);

        $result2 = $qb->innerJoin(PackageType::class, 'pt', ['p.id = pt.id']);
        $this->assertSame($qb, $result2);
    }

    public static function queryBuilderDataProvider(): array
    {
        return [
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p'),
                'expected' => 'SELECT p.* FROM Application.tblPackage p',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['id', 'name']),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name FROM Application.tblPackage p',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['intPackageId', 'name']),
                'expected' => 'SELECT p.intPackageId, p.strPackageName AS p_name FROM Application.tblPackage p',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['p.id', 'pt'])->innerJoin(PackageType::class, 'pt', ['p.packageType = pt.id']),
                'expected' => 'SELECT p.intPackageId AS p_id, pt.* FROM Application.tblPackage p INNER JOIN Application.ublPackageType pt ON p.intPackageTypeId = pt.intPackageTypeId',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select()->innerJoin(PackageType::class, 'pt', ['p.intPackageTypeId = pt.intPackageTypeId']),
                'expected' => 'SELECT p.* FROM Application.tblPackage p INNER JOIN Application.ublPackageType pt ON p.intPackageTypeId = pt.intPackageTypeId',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['p.id'])->innerJoin(StandardModel::class, 'sm', ['p.sm = sm.id']),
                'expected' => 'SELECT p.intPackageId AS p_id FROM Application.tblPackage p',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->innerJoin(PackageType::class, 'pt', ['p.packageType = pt.id']),
                'expected' => 'SELECT p.*, pt.* FROM Application.tblPackage p INNER JOIN Application.ublPackageType pt ON p.intPackageTypeId = pt.intPackageTypeId',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['p', 'pt'])->innerJoin(PackageType::class, 'pt', ['p.packageType = pt.id']),
                'expected' => 'SELECT p.*, pt.* FROM Application.tblPackage p INNER JOIN Application.ublPackageType pt ON p.intPackageTypeId = pt.intPackageTypeId',
            ],
        ];
    }
}