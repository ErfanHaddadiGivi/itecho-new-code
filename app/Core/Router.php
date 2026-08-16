<?php

namespace App\Core;

/**
 * مسیریاب سبک.
 *
 * روش تعریف مسیر در فایل app/routes.php:
 *      $router->get('/product/{slug}', 'ProductController@show');
 *      $router->post('/admin/categories', 'Admin\CategoryController@store');
 *
 * هر چیزی که داخل {} باشد به عنوان پارامتر به متد کنترلر فرستاده می‌شود.
 */
class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:string}> */
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, string $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $this->toRegex($path),
            'handler' => $handler,
        ];
    }

    /**
     * تبدیل '/admin/categories/{id}/edit' به یک الگوی regex
     */
    private function toRegex(string $path): string
    {
        $path = '/' . trim($path, '/');
        // {id} → یک بخش از مسیر که شامل اسلش نیست
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#u';
    }

    /**
     * آدرس درخواست‌شده، بدون کوئری‌استرینگ و بدون نام زیرپوشه نصب
     */
    private function currentPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // حذف کوئری‌استرینگ: /search?q=x → /search
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // اگر سایت در زیرپوشه نصب شده، آن بخش را از ابتدای مسیر حذف کن
        $base = base_path_uri();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = rawurldecode($uri);
        return '/' . trim($uri, '/');
    }

    /**
     * پیدا کردن مسیر مناسب و اجرای آن
     */
    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path   = $this->currentPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                // فقط پارامترهای نام‌دار مثل {id} را نگه دار
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        $this->notFound();
    }

    /**
     * ساخت کنترلر و صدا زدن متد آن
     */
    private function callHandler(string $handler, array $params): void
    {
        [$controllerName, $methodName] = explode('@', $handler);

        $class = 'App\\Controllers\\' . $controllerName;

        if (!class_exists($class)) {
            throw new \RuntimeException("کنترلر پیدا نشد: {$class}");
        }

        $controller = new $class();

        if (!method_exists($controller, $methodName)) {
            throw new \RuntimeException("متد پیدا نشد: {$class}::{$methodName}");
        }

        $controller->{$methodName}(...array_values($params));
    }

    /**
     * صفحه ۴۰۴
     */
    public function notFound(): void
    {
        http_response_code(404);
        View::render('errors/404', ['title' => 'صفحه پیدا نشد'], 'site');
    }
}
