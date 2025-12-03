<?php
// ============================================
// Router.php — Enrutador principal
// ============================================

namespace App\Routes;

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\RateLimiter;

class Router
{
    private array $routes = [];
    private string $basePath = "";

    public function __construct(string $basePath = "")
    {
        $this->basePath = $basePath;

        // CORS global
        (new CorsMiddleware())->handle();
    }

    public function add(string $method, string $path, callable|array $action, array $middlewares = [])
    {
        $path = $this->basePath . $path;

        $this->routes[] = [
            "method"      => strtoupper($method),
            "path"        => $path,
            "action"      => $action,
            "middlewares" => $middlewares
        ];
    }

    public function get($path, $action, $middlewares = [])   { $this->add("GET", $path, $action, $middlewares); }
    public function post($path, $action, $middlewares = [])  { $this->add("POST", $path, $action, $middlewares); }
    public function put($path, $action, $middlewares = [])   { $this->add("PUT", $path, $action, $middlewares); }
    public function delete($path, $action, $middlewares = []){ $this->add("DELETE", $path, $action, $middlewares); }

    public function resolve()
    {
        $requestUri = strtok($_SERVER["REQUEST_URI"], '?');
        $requestMethod = $_SERVER["REQUEST_METHOD"];

        foreach ($this->routes as $route) {

            $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route["path"]);

            if ($requestMethod === $route["method"] && preg_match("#^$pattern$#", $requestUri, $matches)) {

                array_shift($matches);

                // Ejecutar middlewares
                foreach ($route['middlewares'] as $mw) {
                    $result = $this->runMiddleware($mw);

                    if ($result !== true) { return; }
                }

                // Ejecutar acción (Controller)
                if (is_array($route["action"])) {
                    $controller = new $route["action"][0];
                    $method     = $route["action"][1];
                    return $controller->$method(...$matches);
                }

                return call_user_func_array($route["action"], $matches);
            }
        }

        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Ruta no encontrada"
        ]);
    }

    private function runMiddleware($mw)
    {
        // Ejemplo: ['auth'] o ['role:admin']
        if (is_string($mw)) {
            if ($mw === "auth") {
                return (new AuthMiddleware())->handle();
            }
            if (str_starts_with($mw, "role:")) {
                $roles = explode(",", substr($mw, 5));
                return (new RoleMiddleware())->handle($roles);
            }
            if (str_starts_with($mw, "rate:")) {
                $parts = explode(":", $mw); // rate:key:max:sec
                return (new RateLimiter())->handle($parts[1], (int)$parts[2], (int)$parts[3]);
            }
        }

        // Si es objeto o cierre
        if (is_callable($mw)) return $mw();

        return true;
    }
}
