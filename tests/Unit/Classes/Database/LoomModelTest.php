<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Tests\Classes\Database;

use Loom\FrameworkComponent\Classes\Database\DatabaseConnection;
use Loom\FrameworkComponent\Classes\Database\LoomModel;
use Loom\FrameworkComponent\TestData\Database\Model\Package;
use PHPUnit\Framework\TestCase;

class LoomModelTest extends TestCase
{
    public function setUp(): void
    {
        LoomModel::setDatabaseConnection(
            new DatabaseConnection(
                'mysql:host=framework-mysql;port=3306;',
                'root',
                'docker'
            )
        );
    }

    /**
     * @throws \ReflectionException
     */
    public function testGet()
    {
        $packages = Package::select()->get();

        self::assertIsArray($packages);
    }
}