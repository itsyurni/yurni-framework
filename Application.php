<?php

namespace yurni;

use yurni\Database\QueryBuilder;
use yurni\Router\Router;
use yurni\Http\Request;
use yurni\Http\Response;

/**
 * Application Core Class
 * Represents the entry point and manages the lifecycle of requests and responses.
 */
class Application
{
    /**
     * @var Application|null Singleton instance of the application
     */
    protected static ?Application $instance = null;

    /**
     * @var Router Router instance responsible for URL mapping
     */
    protected Router $router;

    /**
     * @var string Base path of the project
     */
    protected string $basePath;

    /**
     * @var Request Current request object
     */
    public Request $request;

    /**
     * @var Response Response object
     */
    public Response $response;

    /**
     * @var array Registered Middlewares in the application
     */
    protected array $middlewares = [];

    /**
     * @var array Default data passed to the template engine
     */
    protected array $viewAttr = [];

    /**
     * @var Container|null Singleton instance of the DI Container
     */
    private ?Container $containerInstance = null;

    /**
     * Application constructor.
     * Initializes request, response, router, and environment.
     *
     * @param string $basePath Base project path (optional — auto-detected if empty)
     */
    public function __construct(string $basePath = '')
    {
        self::$instance = $this;

        // Determine project base path
        $this->basePath = $basePath !== '' ? realpath($basePath) : $this->detectBasePath();

        // Start session only once per request lifecycle
        if (session_status() === PHP_SESSION_NONE) {
            $this->configureSession();
            session_start();
        }

        $this->registerErrorHandling();
        $this->request = new Request($this);
        $this->response = new Response();
        $this->router = new Router($this);
        $this->loadEnv();
        $this->loadViewAttr();

        // Register CSRF as a ready-to-use middleware
        $this->setMiddleware('csrf', \yurni\Security\Csrf::class);
    }

    /**
     * Automatically detect the project base path.
     * Searches upwards from the caller file until composer.json or .env is found.
     *
     * @return string
     */
    private function detectBasePath(): string
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $callerFile = $caller[1]['file'] ?? __FILE__;
        $dir = dirname($callerFile);

        $current = $dir;
        while ($current !== dirname($current)) {
            if (file_exists($current . '/composer.json') || file_exists($current . '/.env')) {
                return realpath($current);
            }
            $current = dirname($current);
        }

        return realpath($dir);
    }

    /**
     * Configure session security options before session_start().
     */
    private function configureSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? null) === '443';

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
    }

    /**
     * Load environment variables from .env file.
     */
    private function loadEnv(): void
    {
        $envFile = $this->basePath . '/.env';

        if (class_exists('Dotenv\\Dotenv')) {
            $dotenv = \Dotenv\Dotenv::createImmutable($this->basePath);
            $dotenv->safeLoad();
        } elseif (file_exists($envFile)) {
            $this->loadEnvManually($envFile);
        }

        Config::load($_ENV);
    }

    /**
     * Manually load .env file if phpdotenv is not installed.
     *
     * @param string $envFile
     */
    private function loadEnvManually(string $envFile): void
    {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                [$name, $value] = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }
    }

    /**
     * Register centralized error and exception handlers for the framework.
     */
    private function registerErrorHandling(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public function handleException(\Throwable $e): void
    {
        if (headers_sent($file, $line)) {
            error_log("Exception after headers sent in {$file}:{$line} — {$e->getMessage()}");
        }

        $this->renderException($e);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        $exception = new \ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        );

        $this->renderException($exception);
    }

    /**
     * Initialize core variables available in all Views.
     */
    private function loadViewAttr(): void
    {
        $this->viewAttr = [
            'app' => $this,
            'appRequest' => $this->request,
            'appResponse' => $this->response,
        ];
    }

    /**
     * Add new data to be available in Views.
     *
     * @param array $args
     * @return self
     */
    public function setViewAttr(array $args = []): self
    {
        foreach ($args as $key => $val) {
            $this->viewAttr[$key] = $val;
        }
        return $this;
    }

    /**
     * Get variables available for Views.
     *
     * @return array
     */
    public function getViewAttr(): array
    {
        return $this->viewAttr;
    }

    /**
     * Register a new Middleware.
     *
     * @param string                 $name     Middleware name
     * @param callable|string|array  $callable Implementation
     */
    public function setMiddleware(string $name, $callable): void
    {
        $this->middlewares[$name] = $callable;
    }

    /**
     * Execute and retrieve the requested Middleware.
     * Supports: Closure, [Class, method], Class::class (invokable)
     *
     * @param string $name Middleware name
     * @return mixed
     */
    public function getMiddleware(string $name): mixed
    {
        if (!$this->hasMiddleware($name)) {
            return false;
        }

        $middleware = $this->middlewares[$name];

        if (is_string($middleware) && class_exists($middleware)) {
            $middleware = [new $middleware(), '__invoke'];
        }

        return $this->container()->call($middleware);
    }

    /**
     * Check if a Middleware exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasMiddleware(string $name): bool
    {
        return isset($this->middlewares[$name]);
    }

    /**
     * Get the Dependency Injection Container instance.
     *
     * @return Container
     */
    public function container(): Container
    {
        if ($this->containerInstance === null) {
            $this->containerInstance = new Container();

            // Register instances as Singletons
            $this->containerInstance->instance(get_class($this->response), $this->response);
            $this->containerInstance->instance(get_class($this->request), $this->request);
            $this->containerInstance->instance(get_class($this), $this);
            $this->containerInstance->instance(Db::class, Db::getInstance());
            $this->containerInstance->bind(
                QueryBuilder::class,
                static fn(Container $c): QueryBuilder => Db::getInstance()->query()
            );

            // Inject with short names for Closures
            $this->containerInstance->injectArgs([
                get_class($this->response) => $this->response,
                get_class($this->request) => $this->request,
                get_class($this) => $this,
                Db::class => Db::getInstance(),
                'app' => $this,
                'request' => $this->request,
                'response' => $this->response,
            ]);
        }

        return $this->containerInstance;
    }

    /**
     * Get the Router instance.
     *
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get the Response object.
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Get the Request object.
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get the project base path.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get the current Application instance (Singleton).
     *
     * @return Application|null
     */
    public static function getInstance(): ?Application
    {
        return self::$instance;
    }

    /********************************************************************************
     * Router Proxy Methods
     *******************************************************************************/

    /**
     * Register a GET route.
     *
     * @param  string                $path
     * @param  callable|string|array $callback
     * @return \yurni\Router\Route
     */
    public function get(string $path, $callback): \yurni\Router\Route
    {
        return $this->router->register(['get'], $path, $callback);
    }

    /**
     * Register a POST route.
     *
     * @param  string                $path
     * @param  callable|string|array $callback
     * @return \yurni\Router\Route
     */
    public function post(string $path, $callback): \yurni\Router\Route
    {
        return $this->router->register(['post'], $path, $callback);
    }

    /**
     * Register a PATCH route.
     *
     * @param  string                $path
     * @param  callable|string|array $callback
     * @return \yurni\Router\Route
     */
    public function patch(string $path, $callback): \yurni\Router\Route
    {
        return $this->router->register(['patch'], $path, $callback);
    }

    /**
     * Register a PUT route.
     *
     * @param  string                $path
     * @param  callable|string|array $callback
     * @return \yurni\Router\Route
     */
    public function put(string $path, $callback): \yurni\Router\Route
    {
        return $this->router->register(['put'], $path, $callback);
    }

    /**
     * Register a DELETE route.
     *
     * @param  string                $path
     * @param  callable|string|array $callback
     * @return \yurni\Router\Route
     */
    public function delete(string $path, $callback): \yurni\Router\Route
    {
        return $this->router->register(['delete'], $path, $callback);
    }

    /**
     * Register a route that responds to any HTTP method.
     *
     * @param  string                $path
     * @param  callable|string|array $callback
     * @return \yurni\Router\Route
     */
    public function any(string $path, $callback): \yurni\Router\Route
    {
        return $this->router->register(['get', 'post', 'put', 'delete', 'patch'], $path, $callback);
    }

    /**
     * Register a route for specific HTTP methods.
     *
     * @param  array                 $methods
     * @param  string                $path
     * @param  callable|string|array $callback
     * @return \yurni\Router\Route
     */
    public function only(array $methods, string $path, $callback): \yurni\Router\Route
    {
        return $this->router->register($methods, $path, $callback);
    }

    /**
     * Run the application (resolve request and return response).
     *
     * @return void
     */
    public function run(): void
    {
        try {
            echo $this->router->resolve();
        } catch (\Throwable $e) {
            $this->renderException($e);
        }
    }

    /**
     * Load and render an error page as a template.
     *
     * @param string $view Template name (e.g., '404', '500', 'exception')
     * @param array $data Data passed to the view
     * @return void
     */
    public function renderErrorPage(string $view, array $data = []): void
    {
        extract($data);

        $customView = $this->basePath . '/app/views/Exception/' . $view . '.php';
        $defaultView = __DIR__ . '/View/Exception/' . $view . '.php';

        if (file_exists($customView)) {
            require $customView;
        } elseif (file_exists($defaultView)) {
            require $defaultView;
        } else {
            echo "<h1>Error {$view}</h1>";
        }
    }

    /**
     * Handle exceptions and render the error screen.
     *
     * @param \Throwable $e
     */
    private function renderException(\Throwable $e): void
    {
        $source = $this->detectExceptionSource($e);

        error_log(
            sprintf(
                '%s: %s in %s on line %d',
                $source,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );

        http_response_code(500);

        $debug = filter_var(
            Config::get('APP_DEBUG', Config::get('app_debug', false)),
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$debug) {
            $this->renderErrorPage('500');
            return;
        }

        $this->renderErrorPage('exception', [
            'e' => $e,
            'errorSource' => $source,
        ]);
    }

    private function detectExceptionSource(\Throwable $e): string
    {
        if (str_starts_with($e::class, 'yurni\\')) {
            return 'Framework error';
        }

        if (str_contains($e->getFile(), __DIR__)) {
            return 'Framework internal error';
        }

        return 'Application error';
    }
}
