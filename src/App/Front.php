<?php

declare(strict_types=1);

namespace Raifs\App;

use Raifs\Home\Sample;

/**
 * The front controller. Routes, then renders. The status route answers with the
 * line count so a probe can tell a working process from a live one.
 */
final class Front
{
    public function __construct(private readonly string $base)
    {
    }

    public function run(): void
    {
        $router = new Router($this->base);
        $views = new Views($this->base);
        $sample = new Sample();

        $router->get('/', static fn (): array => [200, [
            'html' => $views->front($sample),
        ]]);
        $router->get('/status', static fn (): array => [200, ['json' => [
            'ok' => true,
            'entries' => $sample->journal()->count(),
            'period' => $sample->income()->period(),
            'assets_cents' => $sample->balance()->total('asset'),
            'balances' => $sample->balance()->balances(),
        ]]]);

        [$status, $body] = $router->handle(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
        );

        http_response_code($status);
        if (isset($body['json'])) {
            header('Content-Type: application/json');
            echo json_encode($body['json'], JSON_UNESCAPED_SLASHES), "\n";

            return;
        }
        if (isset($body['html'])) {
            header('Content-Type: text/html; charset=utf-8');
            echo $body['html'];

            return;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo $views->notFound((string) ($body['path'] ?? '/'));
    }
}
