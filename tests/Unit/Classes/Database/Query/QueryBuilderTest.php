<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Tests\Classes\Database\Query;

use Data\Database\Model\StandardModel;
use Loom\FrameworkComponent\Classes\Database\DatabaseConnection;
use Loom\FrameworkComponent\Classes\Database\Query\QueryBuilder;
use Loom\FrameworkComponent\TestData\Database\Model\Package;
use Loom\FrameworkComponent\TestData\Database\Model\PackageType;
use Loom\FrameworkComponent\TestData\Database\Model\Role;
use Loom\FrameworkComponent\TestData\Database\Model\User;
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
    public function testGetQueryString(QueryBuilder $queryBuilder, string $expected, array $parameters = []): void
    {
        self::assertEquals($expected, $queryBuilder->getQueryString());
        self::assertEquals($parameters, $queryBuilder->getParameters());
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
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner FROM Application.tblPackage p',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->innerJoin(PackageType::class, 'pt', ['p.packageType = pt.id'])->whereIn('pt.name', ['Package Type A', 'Package Type B']),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner, pt.intPackageTypeId AS pt_id, pt.strPackageTypeName AS pt_name FROM Application.tblPackage p INNER JOIN Application.ublPackageType pt ON p.intPackageTypeId = pt.intPackageTypeId WHERE pt.strPackageTypeName IN (?, ?)',
                'parameters' => ['Package Type A', 'Package Type B'],
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->orderBy('p.id', 'DESC'),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner FROM Application.tblPackage p ORDER BY p.intPackageId DESC',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->orderBy('p.intPackageId', 'DESC'),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner FROM Application.tblPackage p ORDER BY p.intPackageId DESC',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['id', 'name']),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name FROM Application.tblPackage p',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['intPackageId', 'name']),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name FROM Application.tblPackage p',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['intPackageId', 'name'])->whereIn('p.id', [1, 2, 3]),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name FROM Application.tblPackage p WHERE p.intPackageId IN (?, ?, ?)',
                'parameters' => [1, 2, 3],
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['p.id', 'pt'])->innerJoin(PackageType::class, 'pt', ['p.packageType = pt.id']),
                'expected' => 'SELECT p.intPackageId AS p_id, pt.intPackageTypeId AS pt_id, pt.strPackageTypeName AS pt_name FROM Application.tblPackage p INNER JOIN Application.ublPackageType pt ON p.intPackageTypeId = pt.intPackageTypeId',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select()->innerJoin(PackageType::class, 'pt', ['p.intPackageTypeId = pt.intPackageTypeId']),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner, pt.intPackageTypeId AS pt_id, pt.strPackageTypeName AS pt_name FROM Application.tblPackage p INNER JOIN Application.ublPackageType pt ON p.intPackageTypeId = pt.intPackageTypeId',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['p.id'])->innerJoin(StandardModel::class, 'sm', ['p.sm = sm.id']),
                'expected' => 'SELECT p.intPackageId AS p_id FROM Application.tblPackage p',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->innerJoin(PackageType::class, 'pt', ['p.packageType = pt.id']),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner, pt.intPackageTypeId AS pt_id, pt.strPackageTypeName AS pt_name FROM Application.tblPackage p INNER JOIN Application.ublPackageType pt ON p.intPackageTypeId = pt.intPackageTypeId',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['p', 'pt'])->innerJoin(PackageType::class, 'pt', ['p.packageType = pt.id']),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner, pt.intPackageTypeId AS pt_id, pt.strPackageTypeName AS pt_name FROM Application.tblPackage p INNER JOIN Application.ublPackageType pt ON p.intPackageTypeId = pt.intPackageTypeId',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')
                    ->select()
                    ->innerJoin(PackageType::class, 'pt', ['p.intPackageTypeId = pt.intPackageTypeId'])
                    ->where('pt.name', 'Package Type A'),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner, pt.intPackageTypeId AS pt_id, pt.strPackageTypeName AS pt_name FROM Application.tblPackage p INNER JOIN Application.ublPackageType pt ON p.intPackageTypeId = pt.intPackageTypeId WHERE pt.strPackageTypeName = ?',
                'parameters' => ['Package Type A'],
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')
                    ->select()
                    ->innerJoin(PackageType::class, 'pt', ['p.intPackageTypeId = pt.intPackageTypeId'])
                    ->where('pt.strPackageTypeName', 'Package Type A'),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner, pt.intPackageTypeId AS pt_id, pt.strPackageTypeName AS pt_name FROM Application.tblPackage p INNER JOIN Application.ublPackageType pt ON p.intPackageTypeId = pt.intPackageTypeId WHERE pt.strPackageTypeName = ?',
                'parameters' => ['Package Type A'],
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['id'])->where('p.name', 'Package B'),
                'expected' => 'SELECT p.intPackageId AS p_id FROM Application.tblPackage p WHERE p.strPackageName = ?',
                'parameters' => ['Package B'],
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select(['id'])->where('p.strPackageName', 'Package B'),
                'expected' => 'SELECT p.intPackageId AS p_id FROM Application.tblPackage p WHERE p.strPackageName = ?',
                'parameters' => ['Package B'],
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select()->innerJoin(User::class, 'u', ['p.owner = u.id']),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner, u.intUserId AS u_id, u.strUsername AS u_username, u.strEmail AS u_email, u.intRoleId AS u_role FROM Application.tblPackage p INNER JOIN Security.tblUser u ON p.intOwnerId = u.intUserId',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select()->innerJoin(User::class, 'u', ['p.owner = u.id'])->innerJoin(Role::class, 'r', ['u.role = r.id']),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner, u.intUserId AS u_id, u.strUsername AS u_username, u.strEmail AS u_email, u.intRoleId AS u_role, r.intRoleId AS r_id, r.strRoleName AS r_name, r.strRoleHandle AS r_handle FROM Application.tblPackage p INNER JOIN Security.tblUser u ON p.intOwnerId = u.intUserId INNER JOIN Security.ublRole r ON u.intRoleId = r.intRoleId',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select()->innerJoin(User::class, 'u', ['p.owner = u.id'])->innerJoin(Role::class, 'r', ['u.role = r.id'])->limit(1),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner, u.intUserId AS u_id, u.strUsername AS u_username, u.strEmail AS u_email, u.intRoleId AS u_role, r.intRoleId AS r_id, r.strRoleName AS r_name, r.strRoleHandle AS r_handle FROM Application.tblPackage p INNER JOIN Security.tblUser u ON p.intOwnerId = u.intUserId INNER JOIN Security.ublRole r ON u.intRoleId = r.intRoleId LIMIT 1',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select()->innerJoin(User::class, 'u', ['p.owner = u.id'])->innerJoin(Role::class, 'r', ['u.role = r.id'])->orderBy('u.id')->limit(1),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner, u.intUserId AS u_id, u.strUsername AS u_username, u.strEmail AS u_email, u.intRoleId AS u_role, r.intRoleId AS r_id, r.strRoleName AS r_name, r.strRoleHandle AS r_handle FROM Application.tblPackage p INNER JOIN Security.tblUser u ON p.intOwnerId = u.intUserId INNER JOIN Security.ublRole r ON u.intRoleId = r.intRoleId ORDER BY u.intUserId ASC LIMIT 1',
            ],
            [
                'queryBuilder' => new QueryBuilder(Package::class, 'p')->select()->innerJoin(User::class, 'u', ['p.owner = u.id'])->innerJoin(Role::class, 'r', ['u.role = r.id'])->orderBy('u.intUserId')->limit(1),
                'expected' => 'SELECT p.intPackageId AS p_id, p.strPackageName AS p_name, p.intPackageTypeId AS p_packageType, p.intOwnerId AS p_owner, u.intUserId AS u_id, u.strUsername AS u_username, u.strEmail AS u_email, u.intRoleId AS u_role, r.intRoleId AS r_id, r.strRoleName AS r_name, r.strRoleHandle AS r_handle FROM Application.tblPackage p INNER JOIN Security.tblUser u ON p.intOwnerId = u.intUserId INNER JOIN Security.ublRole r ON u.intRoleId = r.intRoleId ORDER BY u.intUserId ASC LIMIT 1',
            ],
        ];
    }
}