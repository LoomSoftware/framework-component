<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent;

use Dotenv\Dotenv;
use Loom\DependencyInjectionComponent\DependencyContainer;
use Loom\DependencyInjectionComponent\DependencyManager;
use Loom\DependencyInjectionComponent\Exception\NotFoundException;
use Loom\FrameworkComponent\Classes\Core\Middleware\MiddlewareHandler;
use Loom\FrameworkComponent\Classes\Database\DatabaseConnection;
use Loom\FrameworkComponent\Classes\Database\LoomModel;
use Loom\FrameworkComponent\Controller\LoomController;
use Loom\HttpComponent\Response;
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
    private MiddlewareHandler $middlewareHandler;

    private const array VALID_CONFIG_EXTENSIONS = ['yaml', 'yml'];

    /**
     * @throws \Exception|NotFoundException
     */
    public function __construct(
        private readonly string $configDirectory,
        private readonly string $cacheDirectory,
        private readonly string $templateDirectory
    ) {
        Dotenv::createImmutable($this->configDirectory)->load();

        $this->container = new DependencyContainer();
        $this->dependencyManager = new DependencyManager($this->container);
        $this->router = new Router($this->container);
        $this->middlewareHandler = new MiddlewareHandler();

        $this->setPageNotFoundController();
        $this->loadDatabaseSettings();

        LoomController::setDirectories($this->templateDirectory, sprintf('%s/views', $this->cacheDirectory));

        $this->load();
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function run(RequestInterface $request): ResponseInterface
    {
        $response = $this->middlewareHandler->handle($request, new Response());

        if ($response->getStatusCode() === 200) {
            $response = $this->router->handleRequest($request);
        }

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

    /**
     * @throws NotFoundException
     */
    private function load(): void
    {
        $this->loadDependencies();
        $this->loadRoutes();
    }

    private function setPageNotFoundController(): void
    {
        if (isset($_ENV['PAGE_NOT_FOUND_CONTROLLER'])) {
            $this->router->setNotFoundHandler($_ENV['PAGE_NOT_FOUND_CONTROLLER']);
        }
    }

    private function loadDatabaseSettings(): void
    {
        if (isset($_ENV['DATABASE_HOST']) && isset($_ENV['DATABASE_USER']) && isset($_ENV['DATABASE_PASSWORD'])) {
            Loom::$databaseConnection = new DatabaseConnection(
                sprintf('%s:host=%s;port=%s;', $_ENV['DATABASE_DRIVER'] ?? 'mysql', $_ENV['DATABASE_HOST'], $_ENV['DATABASE_PORT'] ?? 3306),
                $_ENV['DATABASE_USER'],
                $_ENV['DATABASE_PASSWORD']
            );
            LoomModel::setDatabaseConnection(Loom::$databaseConnection);
        }
    }

    /**
     * @throws NotFoundException
     */
    private function loadDependencies(): void
    {
        foreach (self::VALID_CONFIG_EXTENSIONS as $extension) {
            $filePath = sprintf('%s/services.%s', $this->configDirectory, $extension);

            if (file_exists($filePath)) {
                $this->dependencyManager
                    ->loadDependenciesFromFile($filePath);
                break;
            }
        }
    }

    private function loadRoutes(): void
    {
        foreach (self::VALID_CONFIG_EXTENSIONS as $extension) {
            $filePath = sprintf('%s/routes.%s', $this->configDirectory, $extension);

            if (file_exists($filePath)) {
                $this->router
                    ->loadRoutesFromFile($filePath);
                break;
            }
        }
    }
}