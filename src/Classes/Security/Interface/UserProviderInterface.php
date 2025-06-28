<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Security\Interface;

interface UserProviderInterface
{
    public function loadByIdentifier(int|string $identifier): ?UserInterface;
    public function loadFromSession(): ?UserInterface;
}