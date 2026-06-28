<?php

namespace App\Core;

/**
 * Classe Router
 * Gère la correspondance entre les URLs et les Controllers.
 *
 * Usage dans index.php :
 *   $router = new Router();
 *   $router->get('/trajets', 'TrajetController@index');
 *   $router->post('/reservation', 'ReservationController@store');
 *   $router->dispatch();
 *
 * Format des URLs : /controller/action/id
 * Exemple : GET  /trajets          → TrajetController::index()
 *           GET  /trajets/1        → TrajetController::show(1)
 *           POST /reservation      → ReservationController::store()
 */
class Router
{
    /** @var array<string, array<string, array{controller: string, method: string}>> */
    private array $routes = [];

    // ── Enregistrement des routes ─────────────────────────────────────────────

    public function get(string $path, string $action): void
    {
        $this->addRoute('GET', $path, $action);
    }

    public function post(string $path, string $action): void
    {
        $this->addRoute('POST', $path, $action);
    }

    /**
     * @param string $action  Format : "NomController@nomMethode"
     *                        Exemple : "TrajetController@index"
     */
    private function addRoute(string $httpMethod, string $path, string $action): void
    {
        [$controller, $method] = explode('@', $action);

        $this->routes[$httpMethod][$path] = [
            'controller' => $controller,
            'method'     => $method,
        ];
    }

    // ── Dispatch ──────────────────────────────────────────────────────────────

    /**
     * Analyse l'URL courante et appelle le bon Controller.
     * Extrait un éventuel paramètre numérique (id) dans l'URL.
     *
     * @throws \RuntimeException si la route n'est pas trouvée
     */
    public function dispatch(): void
    {
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri        = $this->parseUri();

        // Cherche une route exacte d'abord
        if (isset($this->routes[$httpMethod][$uri['path']])) {
            $route = $this->routes[$httpMethod][$uri['path']];
            $this->call($route['controller'], $route['method'], $uri['id']);
            return;
        }

        // Cherche une route avec paramètre (/trajets/1 → /trajets/:id)
        $basePath = $uri['base'];
        if ($uri['id'] !== null && isset($this->routes[$httpMethod][$basePath])) {
            $route = $this->routes[$httpMethod][$basePath];
            $this->call($route['controller'], $route['method'], $uri['id']);
            return;
        }

        $this->notFound();
    }

    // ── Résolution du Controller ──────────────────────────────────────────────

    /**
     * Instancie le Controller et appelle la méthode avec l'id optionnel.
     *
     * @throws \RuntimeException si le Controller ou la méthode est introuvable
     */
    private function call(string $controllerName, string $methodName, ?int $id): void
    {
        $class = "App\\Controllers\\{$controllerName}";

        if (!class_exists($class)) {
            throw new \RuntimeException("Controller introuvable : {$class}");
        }

        $controller = new $class();

        if (!method_exists($controller, $methodName)) {
            throw new \RuntimeException("Méthode introuvable : {$class}::{$methodName}()");
        }

        $id !== null
            ? $controller->$methodName($id)
            : $controller->$methodName();
    }

    // ── Utilitaires ───────────────────────────────────────────────────────────

    /**
     * Extrait le chemin propre et l'éventuel id depuis l'URI.
     *
     * Exemple : /trajets/42?foo=bar
     *   → path = '/trajets/42', base = '/trajets', id = 42
     *
     * @return array{path: string, base: string, id: int|null}
     */
    private function parseUri(): array
    {
        $raw  = $_SERVER['REQUEST_URI'] ?? '/';
        $path = strtok($raw, '?');           // retire la query string
        $path = rtrim($path, '/') ?: '/';   // retire le slash final

        $segments = explode('/', trim($path, '/'));
        $last     = end($segments);
        $id       = is_numeric($last) ? (int) $last : null;

        // base = chemin sans le dernier segment si c'est un id
        $base = $id !== null
            ? '/' . implode('/', array_slice($segments, 0, -1))
            : $path;

        return ['path' => $path, 'base' => $base, 'id' => $id];
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo "<h1>404 — Page introuvable</h1>";
        exit;
    }
}
