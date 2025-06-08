<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\TestData\Database\Model;

use Loom\FrameworkComponent\Classes\Database\Attributes\Column;
use Loom\FrameworkComponent\Classes\Database\Attributes\ID;
use Loom\FrameworkComponent\Classes\Database\Attributes\Schema;
use Loom\FrameworkComponent\Classes\Database\Attributes\Table;
use Loom\FrameworkComponent\Classes\Database\LoomModel;

#[Schema(name: 'Application')]
#[Table(name: 'tblModel')]
class Model extends LoomModel
{
    #[ID]
    #[Column(name: 'intModelId')]
    protected int $id;
}