<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Core\Middleware;

use Loom\HttpComponent\Request;
use Loom\HttpComponent\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, Response $response, callable $next): Response;
}