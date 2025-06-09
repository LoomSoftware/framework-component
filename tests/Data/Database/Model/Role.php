<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\TestData\Database\Model;

use Loom\FrameworkComponent\Classes\Database\Attributes\Column;
use Loom\FrameworkComponent\Classes\Database\Attributes\ID;
use Loom\FrameworkComponent\Classes\Database\Attributes\Schema;
use Loom\FrameworkComponent\Classes\Database\Attributes\Table;
use Loom\FrameworkComponent\Classes\Database\LoomModel;

#[Schema(name: 'Security')]
#[Table(name: 'ublRole')]
class Role extends LoomModel
{
    #[ID]
    #[Column(name: 'intRoleId')]
    protected int $id;

    #[Column(name: 'strRoleName')]
    protected string $name;

    #[Column(name: 'strRoleHandle')]
    protected string $handle;
}