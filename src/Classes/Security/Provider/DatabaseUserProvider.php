<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Security\Provider;

use Loom\FrameworkComponent\Classes\Database\LoomModel;
use Loom\FrameworkComponent\Classes\Security\Interface\UserInterface;

class DatabaseUserProvider
{
    private LoomModel&UserInterface $user;
}