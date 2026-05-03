<?php
namespace yurni\Router;

use yurni\Application;
use yurni\Http\Response;
use yurni\Http\Request;

/**
 * كلاس الموجه (Router)
 * مسؤول عن تسجيل ومعالجة الروابط (Routes) وتوجيهها للمتحكمات (Controllers) المناسبة.
 */
class Router
{
    /**
     * @var Application كائن التطبيق الأساسي
     */
    protected Application $app;

    /**
     * @var Request كائن الطلب
     */
    protected Request $request;

    /**
     * @var Response كائن الاستجابة
     */
    protected Response $response;

    /**
     * @var array مصفوفة تحتوي على كل المسارات المسجلة
     */
    protected array $routes = [];

    /**
     * @var array مصفوفة دوال معالجة الأخطاء (مثل 404, 500)
     */
    protected array $handle = [];

    /**
     * منشئ الكلاس (Constructor)
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
        $this->response = $this->app->response;

        // استجابة افتراضية في حالة عدم العثور على المسار
        $this->handle("404", function () {
            http_response_code(404);
            $this->app->renderErrorPage('404');
        });
    }

    // -------------------------------------------------------------------------
    // Route Registration
    // -------------------------------------------------------------------------

    /**
     * تسجيل مسار جديد
     *
     * @param array|string   $method طرق الطلب (مثل 'get', ['get', 'post'])
     * @param string         $uri    الرابط
     * @param callable|array $action الدالة أو المتحكم المطلوب
     * @return Route
     */
    public function register($method, string $uri, $action): Route
    {
        $routeUri = $this->routeToRegex($uri);
        $route = new Route($method, $routeUri, $action);

        $this->routes[] = $route;
        return $route;
    }

    /**
     * الحصول على جميع المسارات المسجلة
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
     * تسجيل دالة مخصصة لمعالجة أخطاء معينة (مثل 404, 500)
     *
     * @param string   $type     نوع الخطأ
     * @param callable $callback الدالة المراد تنفيذها
     */
    public function handle(string $type, callable $callback): void
    {
        $this->handle[$type] = $callback;
    }

    /**
     * تنفيذ دالة معالجة الخطأ المحفوظة
     *
     * @param string $type
     * @return mixed
     */
    public function getHandle(string $type)
    {
        return $this->app->container()->call($this->handle[$type]);
    }

    // -------------------------------------------------------------------------
    // Core
    // -------------------------------------------------------------------------

    /**
     * تحويل رابط المسار إلى تعبير نمطي (Regex) للمطابقة.
     *
     * تدعم صيغة {param} فقط — المستخدم يتحكم بالنوع بنفسه.
     *
     * مثال:
     *   /user/{id}        →  /^\/user\/(?P<id>[^\/]+)$/i
     *   /post/{slug}/edit →  /^\/post\/(?P<slug>[^\/]+)\/edit$/i
     *
     * @param string $route
     * @return string
     */
    public function routeToRegex(string $route): string
    {
        // تهيئة الـ Slashes
        $route = preg_replace("/\\//", "\/", $route);

        // تحويل {param} إلى Named Capture Group
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
     * البحث عن المسار المطابق للطلب الحالي
     *
     * @param string $path   رابط الطلب الفعلي
     * @param string $method طريقة الطلب (مثل 'get', 'post')
     * @return Route|false
     */
    protected function findRoute(string $path, string $method)
    {
        foreach ($this->getRoutes() as $route) {
            if (
                preg_match($route->getUri(), $path, $matches) &&
                in_array($method, (array) $route->getMethod())
            ) {
                // تخزين المتغيرات الملتقطة داخل كائن المسار
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
     * تنفيذ الدالة المسؤولة عن المسار وإرجاع النتيجة للـ Client
     *
     * @param callable|array $callback
     * @param array          $args
     * @return mixed
     */
    public function resolveCallback($callback, array $args)
    {
        $output = $this->app->container()->injectArgs($args)->call($callback);

        // إذا كان المخرج كائن Response (مثل redirect) نرجعه مباشرة
        if ($output instanceof Response) {
            return $output->body();
        }

        // تحويل المصفوفات تلقائياً إلى JSON
        if (is_array($output)) {
            return $this->response->json($output)->body();
        }

        return $this->response->html($output)->body();
    }

    /**
     * تحليل الطلب الحالي والعثور على المسار المناسب وتنفيذه
     *
     * @return mixed
     */
    public function resolve()
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();

        $route = $this->findRoute($path, $method);

        if (!$route) {
            return $this->getHandle("404");
        }

        // حفظ كائن المسار داخل كائن الطلب
        $this->request->setRoute($route);

        // تشغيل الـ Middlewares المسجلة على هذا المسار
        if (!$this->runMiddlewares($route)) {
            return false;
        }

        return $this->resolveCallback($route->getCallback(), $route->getParam());
    }

    /**
     * تشغيل البرمجيات الوسيطة الخاصة بمسار معين
     *
     * @param Route $route
     * @return bool يعيد false إذا تم حظر الطلب من قبل أحد الـ Middlewares
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
