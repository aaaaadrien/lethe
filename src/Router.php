<?php
declare(strict_types=1);

namespace Lethe;

final class Router
{
    /** @var array<string, callable> */
    private array $routes = [];

    public static function create(): self
    {
        $router = new self();
        $register = require __DIR__ . '/routes.php';
        $register($router);
        return $router;
    }

    public function map(string $action, callable $handler): void
    {
        $this->routes[$action] = $handler;
    }

    public function dispatch(string $action): void
    {
        if (!isset($this->routes[$action])) {
            Response::json(['status' => 'error', 'message' => Language::t('api.unknown_action')], 404);
        }
        ($this->routes[$action])();
    }
}
