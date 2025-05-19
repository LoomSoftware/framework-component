<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent;

use Loom\DependencyInjectionComponent\DependencyContainer;
use Loom\DependencyInjectionComponent\DependencyManager;
use Loom\DependencyInjectionComponent\Exception\NotFoundException;
use Loom\RouterComponent\Router;

final class Loom
{
    private DependencyContainer $container;
    private DependencyManager $dependencyManager;
    private Router $router;

    /**
     * @throws NotFoundException
     */
    public function __construct(private readonly string $configDirectory)
    {
        $this->container = new DependencyContainer();
        $this->dependencyManager = new DependencyManager($this->container);
        $this->router = new Router($this->container);

        $this->loadDependencies();
        $this->loadRoutes();
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