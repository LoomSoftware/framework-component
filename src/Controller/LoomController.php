<?php

declare(strict_types=1);

namespace Loom\FrameworkComponent\Controller;

use Latte\Engine;
use Loom\HttpComponent\Response;
use Loom\HttpComponent\StreamBuilder;
use Psr\Http\Message\ResponseInterface;

class LoomController
{
    protected static string $templateDirectory;
    protected static string $cacheDirectory;

    private Engine $templateEngine;

    public function __construct()
    {
        $this->templateEngine = new Engine();
        $this->templateEngine->setTempDirectory(static::$cacheDirectory);
    }

    public static function setDirectories(string $templateDirectory, string $cacheDirectory): void
    {
        static::$templateDirectory = $templateDirectory;
        static::$cacheDirectory = $cacheDirectory;
    }

    protected function respond(
        string $data,
        int $statusCode = 200,
        string $reasonPhrase = 'OK',
        string $contentType = 'text/html',
        array $headers = []
    ): ResponseInterface {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = $contentType;
        }

        return new Response(
            $statusCode,
            $reasonPhrase,
            $headers,
            StreamBuilder::build($data)
        );
    }

    protected function render(string $template, array $data = []): ResponseInterface
    {
        return $this->respond(
            $this->templateEngine->renderToString(sprintf('%s/%s', static::$templateDirectory, $template), $data)
        );
    }
}