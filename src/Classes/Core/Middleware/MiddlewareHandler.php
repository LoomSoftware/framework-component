<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Classes\Core\Middleware;

use Loom\FrameworkComponent\Classes\Core\Utility\Collection;
use Loom\HttpComponent\Request;
use Loom\HttpComponent\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MiddlewareHandler
{
    private Collection $middleware;

    public function __construct()
    {
        $this->middleware = new Collection();
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middleware->add($middleware);
    }

    public function handle(RequestInterface $request, ResponseInterface $response): Response
    {
        $handler = array_reduce(
            array_reverse($this->middleware->toArray()),
            fn ($next, $middleware) => fn ($request, $response) => $middleware->process($request, $response, $next),
            fn ($request, $response) => $response
        );

        return $handler($request, $response);
    }
}