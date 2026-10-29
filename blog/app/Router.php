<?php
/**
 * Roteador mínimo.
 *
 *   $router->get('/tag/{slug}', fn($p) => Site::archive('tag', $p['slug']));
 *   $router->post('/admin/posts/{id:\d+}', fn($p) => Admin::savePost((int) $p['id']));
 *
 * {nome} aceita qualquer coisa sem barra; {nome:regex} usa a regex dada.
 * Se o handler retornar false, o roteador tenta a próxima rota (e no fim,
 * os redirecionamentos e a página 404).
 */
final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add(['GET'], $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add(['POST'], $pattern, $handler);
    }

    public function any(string $pattern, callable $handler): void
    {
        $this->add(['GET', 'POST'], $pattern, $handler);
    }

    public function dispatch(string $method, string $path): bool
    {
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        foreach ($this->routes as [$methods, $regex, $handler]) {
            if (!in_array($method, $methods, true) || !preg_match($regex, $path, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            if ($handler($params) !== false) {
                return true;
            }
        }
        return false;
    }

    private function add(array $methods, string $pattern, callable $handler): void
    {
        $regex = '';
        $parts = preg_split('#(\{\w+(?::[^}]+)?\})#', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $part) {
            if (preg_match('#^\{(\w+)(?::([^}]+))?\}$#', $part, $m)) {
                $regex .= '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')';
            } else {
                $regex .= preg_quote($part, '#');
            }
        }
        $this->routes[] = [$methods, '#^' . $regex . '$#u', $handler];
    }
}
