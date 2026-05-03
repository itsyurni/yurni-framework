<?php
declare(strict_types=1);

namespace yurni\Router;

use yurni\Application;
use yurni\Http\Response;
use yurni\Http\Request;

/**
 * Router Class
 * Responsible for registering and processing application routes and directing them to the appropriate controllers.
 */
class Router
{
    /**
     * @var Application Core application instance
     */
    protected Application $app;

    /**
     * @var Request Request instance
     */
    protected Request $request;

    /**
     * @var Response Response instance
     */
    protected Response $response;

    /**
     * @var array Array containing all registered routes
     */
    protected array $routes = [];

    /**
     * @var array Array of error handling callbacks (e.g., 404, 500)
     */
    protected array $handle = [];

    /**
     * Router constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
        $this->response = $this->app->response;

        // Default response for Page Not Found
        $this->handle("404", function () {
            http_response_code(404);
            $this->app->renderErrorPage('404');
        });
    }

    // -------------------------------------------------------------------------
    // Route Registration
    // -------------------------------------------------------------------------

    /**
     * Register a new route.
     *
     * @param array|string   $method HTTP methods (e.g., 'get', ['get', 'post'])
     * @param string         $uri    The URI path
     * @param callable|array $action Callback or controller action
     * @return Route
     */
    public function register(array|string $method, string $uri, callable|array $action): Route
    {
        $routeUri = $this->routeToRegex($uri);
        $route = new Route($method, $routeUri, $action);

        $this->routes[] = $route;
        return $route;
    }

    /**
     * Get all registered routes.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    // -------------------------------------------------------------------------
    // Error Handling
    // -------------------------------------------------------------------------

    /**
     * Register a custom callback to handle specific errors (e.g., 404, 500).
     *
     * @param string   $type     Error type/code
     * @param callable $callback The callback function
     */
    public function handle(string $type, callable $callback): void
    {
        $this->handle[$type] = $callback;
    }

    /**
     * Execute a stored error handling callback.
     *
     * @param string $type
     * @return mixed
     */
    public function getHandle(string $type): mixed
    {
        return $this->app->container()->call($this->handle[$type]);
    }

    // -------------------------------------------------------------------------
    // Core
    // -------------------------------------------------------------------------

    /**
     * Convert a route URI into a Regular Expression for matching.
     *
     * Supports {param} syntax for dynamic segments.
     *
     * Example:
     *   /user/{id}        →  /^\/user\/(?P<id>[^\/]+)$/i
     *   /post/{slug}/edit →  /^\/post\/(?P<slug>[^\/]+)\/edit$/i
     *
     * @param string $route
     * @return string
     */
    public function routeToRegex(string $route): string
    {
        // Escape forward slashes
        $route = preg_replace("/\\//", "\/", $route);

        // Convert {param} placeholders into Named Capture Groups
        $route = preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)\}/',
            function ($matches) {
                return '(?P<' . $matches[1] . '>[^\/]+)';
            },
            $route
        );

        return "/^" . $route . "$/i";
    }

    /**
     * Find the route matching the current request.
     *
     * @param string $path   The actual request path
     * @param string $method The HTTP request method
     * @return Route|false
     */
    protected function findRoute(string $path, string $method): Route|false
    {
        foreach ($this->getRoutes() as $route) {
            if (
                preg_match($route->getUri(), $path, $matches) &&
                in_array($method, (array) $route->getMethod())
            ) {
                // Store captured parameters in the Route object
                foreach ($matches as $key => $val) {
                    if (is_string($key)) {
                        $route->setParam($key, $val);
                    }
                }
                return $route;
            }
        }
        return false;
    }

    /**
     * Execute the callback responsible for the route and return the result.
     *
     * @param callable|array $callback
     * @param array          $args
     * @return mixed
     */
    public function resolveCallback(callable|array $callback, array $args): mixed
    {
        $output = $this->app->container()->injectArgs($args)->call($callback);

        // If the output is a Response object, return its body
        if ($output instanceof Response) {
            return $output->body();
        }

        // Automatically convert arrays to JSON
        if (is_array($output)) {
            return $this->response->json($output)->body();
        }

        return $this->response->html($output)->body();
    }

    /**
     * Resolve the current request and execute the appropriate route.
     *
     * @return mixed
     */
    public function resolve(): mixed
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();

        $route = $this->findRoute($path, $method);

        if (!$route) {
            return $this->getHandle("404");
        }

        // Save the Route object within the Request object
        $this->request->setRoute($route);

        // Execute Middlewares registered for this route
        if (!$this->runMiddlewares($route)) {
            return false;
        }

        return $this->resolveCallback($route->getCallback(), $route->getParam());
    }

    /**
     * Execute Middlewares for a specific route.
     *
     * @param Route $route
     * @return bool Returns false if the request was blocked by a middleware
     */
    private function runMiddlewares(\yurni\Router\Route $route): bool
    {
        foreach ($route->getMiddlewares() as $key) {
            if (!$this->app->getMiddleware($key)) {
                if (http_response_code() === 200) {
                    http_response_code(403);
                    $this->app->renderErrorPage('403');
                }
                return false;
            }
        }
        return true;
    }
}
