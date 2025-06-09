<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\TestData\Database\Model;

use Loom\FrameworkComponent\Classes\Database\Attributes\Column;
use Loom\FrameworkComponent\Classes\Database\Attributes\ID;
use Loom\FrameworkComponent\Classes\Database\Attributes\Schema;
use Loom\FrameworkComponent\Classes\Database\Attributes\Table;
use Loom\FrameworkComponent\Classes\Database\LoomModel;

#[Schema(name: 'Application')]
#[Table(name: 'tblPackage')]
class Package extends LoomModel
{
    #[ID]
    #[Column(name: 'intPackageId')]
    protected int $id;

    #[Column(name: 'strPackageName')]
    protected string $name;

    #[Column(name: 'intPackageTypeId')]
    protected PackageType $packageType;

    #[Column(name: 'intOwnerId')]
    protected User $owner;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPackageType(): PackageType
    {
        return $this->packageType;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }
}