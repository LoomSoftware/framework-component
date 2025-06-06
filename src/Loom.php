<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent;

use Dotenv\Dotenv;
use Loom\DependencyInjectionComponent\DependencyContainer;
use Loom\DependencyInjectionComponent\DependencyManager;
use Loom\DependencyInjectionComponent\Exception\NotFoundException;
use Loom\FrameworkComponent\Classes\Database\DatabaseConnection;
use Loom\FrameworkComponent\Controller\LoomController;
use Loom\RouterComponent\Router;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Loom
{
    private static DatabaseConnection $databaseConnection;
    private DependencyContainer $container;
    private DependencyManager $dependencyManager;
    private Router $router;

    /**
     * @throws \Exception|NotFoundException
     */
    public function __construct(
        private readonly string $configDirectory,
        private readonly string $cacheDirectory,
        private readonly string $templateDirectory
    ) {
        $dotenv = Dotenv::createImmutable($this->configDirectory);
        $dotenv->load();

        $this->container = new DependencyContainer();
        $this->dependencyManager = new DependencyManager($this->container);
        $this->router = new Router($this->container);

        if (isset($_ENV['PAGE_NOT_FOUND_CONTROLLER'])) {
            $this->router->setNotFoundHandler($_ENV['PAGE_NOT_FOUND_CONTROLLER']);
        }

        if (isset($_ENV['DATABASE_HOST']) && isset($_ENV['DATABASE_USER']) && isset($_ENV['DATABASE_PASSWORD'])) {
            Loom::$databaseConnection = new DatabaseConnection(
                sprintf('%s:host=%s;port=%s;', $_ENV['DATABASE_DRIVER'] ?? 'mysql', $_ENV['DATABASE_HOST'], $_ENV['DATABASE_PORT'] ?? 3306),
                $_ENV['DATABASE_USER'],
                $_ENV['DATABASE_PASSWORD']
            );
        }

        LoomController::setDirectories($this->templateDirectory, $this->cacheDirectory);

        $this->loadDependencies();
        $this->loadRoutes();
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function run(RequestInterface $request): ResponseInterface
    {
        $response = $this->router->handleRequest($request);

        http_response_code($response->getStatusCode());
        header(sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        return $response;
    }

    public static function getDatabaseConnection(): DatabaseConnection
    {
        return Loom::$databaseConnection;
    }

    /**
     * @throws NotFoundException
     */
    private function loadDependencies(): void
    {
        // @todo: Could be improved.
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