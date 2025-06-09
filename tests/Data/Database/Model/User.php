<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\TestData\Database\Model;

use Loom\FrameworkComponent\Classes\Database\Attributes\Column;
use Loom\FrameworkComponent\Classes\Database\Attributes\ID;
use Loom\FrameworkComponent\Classes\Database\Attributes\Schema;
use Loom\FrameworkComponent\Classes\Database\Attributes\Table;
use Loom\FrameworkComponent\Classes\Database\LoomModel;

#[Schema(name: 'Security')]
#[Table(name: 'tblUser')]
class User extends LoomModel
{
    #[ID]
    #[Column(name: 'intUserId')]
    protected int $id;

    #[Column(name: 'strUsername')]
    protected string $username;

    #[Column(name: 'strEmail')]
    protected string $email;

    #[Column(name: 'intRoleId')]
    protected Role $role;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): Role
    {
        return $this->role;
    }
}