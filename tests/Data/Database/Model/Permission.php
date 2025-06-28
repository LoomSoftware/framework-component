<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\TestData\Database\Model;

use Loom\FrameworkComponent\Classes\Database\Attributes\Column;
use Loom\FrameworkComponent\Classes\Database\Attributes\ID;
use Loom\FrameworkComponent\Classes\Database\Attributes\Schema;
use Loom\FrameworkComponent\Classes\Database\Attributes\Table;
use Loom\FrameworkComponent\Classes\Database\LoomModel;

#[Schema(name: 'Security')]
#[Table(name: 'ublPermission')]
class Permission extends LoomModel
{
    #[ID]
    #[Column(name: 'intPermissionId')]
    protected ?int $id = null;
}