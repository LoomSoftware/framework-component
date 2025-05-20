<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent;

use Loom\DependencyInjectionComponent\DependencyContainer;
use Loom\DependencyInjectionComponent\DependencyManager;
use Loom\DependencyInjectionComponent\Exception\NotFoundException;
use Loom\FrameworkComponent\Controller\LoomController;
use Loom\RouterComponent\Router;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Loom
{
    private DependencyContainer $container;
    private DependencyManager $dependencyManager;
    private Router $router;

    /**
     * @throws NotFoundException
     */
    public function __construct(
        private readonly string $configDirectory,
        private readonly string $cacheDirectory,
        private readonly string $templateDirectory
    ) {
        $this->container = new DependencyContainer();
        $this->dependencyManager = new DependencyManager($this->container);
        $this->router = new Router($this->container);

        LoomController::setDirectories($this->templateDirectory, $this->cacheDirectory);

        $this->loadDependencies();
        $this->loadRoutes();
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        $response = $this->router->handleRequest($request);

        http_response_code($response->getStatusCode());
        header(sprintf('HTTP/%s %s %s', $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase()));

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        return $response;
    }

    /**
     * @throws NotFoundException
     */
    private function loadDependencies(): void
    {
        $files = [
            'services.yaml',
            'services.yml',
        ];

        foreach ($files as $file) {
            $filePath = sprintf('%s/%s', $this->configDirectory, $file);

            if (file_exists($filePath)) {
                $this->dependencyManager
                    ->loadDependenciesFromFile($filePath);
                break;
            }
        }
    }

    private function loadRoutes(): void
    {
        $files = [
            'routes.yaml',
            'routes.yml',
        ];

        foreach ($files as $file) {
            $filePath = sprintf('%s/%s', $this->configDirectory, $file);

            if (file_exists($filePath)) {
                $this->router
                    ->loadRoutesFromFile($filePath);
                break;
            }
        }
    }
}