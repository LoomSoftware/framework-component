<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Core\Utility;

use Loom\FrameworkComponent\Classes\Database\LoomModel;

class ModelCollection extends Collection
{
    /**
     * @var LoomModel[]
     */
    protected array $items = [];
}