<?php
namespace yurni;

use yurni\Database\QueryBuilder;
use yurni\Router\Router;
use yurni\Http\Request;
use yurni\Http\Response;

/**
 * الكلاس الأساسي للإطار (Application)
 * يمثل نقطة الدخول (Entry Point) ويدير دورة حياة الطلبات والاستجابات.
 */
class Application
{
    /**
     * @var Application|null نسخة الـ Singleton من التطبيق
     */
    protected static ?Application $instance = null;

    /**
     * @var Router كائن الموجه المسؤول عن الروابط
     */
    protected Router $router;

    /**
     * @var string المسار الأساسي للمشروع
     */
    protected string $basePath;

    /**
     * @var Request كائن الطلب الحالي
     */
    public Request $request;

    /**
     * @var Response كائن الاستجابة
     */
    public Response $response;

    /**
     * @var array البرمجيات الوسيطة (Middlewares) المسجلة في التطبيق
     */
    protected array $middlewares = [];

    /**
     * @var array البيانات التي سيتم تمريرها بشكل افتراضي لمحرك القوالب
     */
    protected array $viewAttr = [];

    /**
     * @var Container|null نسخة Singleton من حاوية الـ DI لإعادة استخدامها
     */
    private ?Container $containerInstance = null;

    /**
     * منشئ الكلاس الأساسي
     * يقوم بتهيئة كائنات الطلب، الاستجابة، الموجه وإعداد بيئة العمل
     * 
     * @param string $basePath المسار الأساسي للمشروع (اختياري)
     */
    public function __construct(string $basePath = '')
    {
        self::$instance = $this;
        // تحديد المسار الأساسي للمشروع
        $this->basePath = $basePath ?: realpath(__DIR__ . '/../');

        // تشغيل الجلسة مرة واحدة فقط في دورة حياة الطلب
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->request = new Request($this);
        $this->response = new Response();
        $this->router = new Router($this);
        $this->loadEnv();
        $this->loadViewAttr();
    }

    /**
     * تحميل متغيرات البيئة من ملف .env وتخزينها في نظام الإعدادات
     */
    private function loadEnv()
    {
        $envFile = $this->basePath . '/.env';
        if (class_exists(\Dotenv\Dotenv::class)) {
            $dotenv = \Dotenv\Dotenv::createImmutable($this->basePath);
            $dotenv->safeLoad();
        } elseif (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0)
                    continue;
                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);
                    $_ENV[trim($name)] = trim($value);
                }
            }
        }
        Config::load($_ENV);
    }

    /**
     * تهيئة المتغيرات الأساسية المتاحة دائماً داخل قوالب العرض (Views)
     */
    private function loadViewAttr()
    {
        return $this->viewAttr = [
            "app" => $this,
            "appRequest" => $this->request,
            "appResponse" => $this->response
        ];
    }



    /**
     * إضافة بيانات جديدة لتكون متاحة داخل قوالب العرض
     * 
     * @param array $args مصفوفة البيانات
     * @return self
     */
    public function setViewAttr($args = [])
    {
        foreach ($args as $key => $val) {
            $this->viewAttr[$key] = $val;
        }
        return $this;
    }

    /**
     * الحصول على البيانات المتاحة لقوالب العرض
     * 
     * @return array
     */
    public function getViewAttr()
    {
        return $this->viewAttr;
    }

    /**
     * تسجيل برمجية وسيطة (Middleware) جديدة
     * 
     * @param string $name اسم الـ Middleware
     * @param callable|string|array $callable الكلاس أو الدالة المسؤولة
     */
    public function setMiddleware($name, $callable)
    {
        $this->middlewares[$name] = $callable;
    }

    /**
     * تنفيذ والحصول على الـ Middleware المطلوب
     * 
     * @param string $name اسم الـ Middleware
     * @return mixed
     */
    public function getMiddleware($name)
    {
        return $this->hasMiddleware($name) ? $this->container()->call($this->middlewares[$name]) : false;
    }

    /**
     * التحقق من وجود الـ Middleware
     * 
     * @param string $name اسم الـ Middleware
     * @return bool
     */
    public function hasMiddleware($name)
    {
        return isset($this->middlewares[$name]);
    }

    /**
     * الحصول على حاوية الحقن التلقائي (Dependency Injection Container)
     * تعتمد نمط Singleton لضمان استخدام نسخة واحدة فقط طوال دورة حياة الطلب،
     * مما يحافظ على الكائنات المسجلة مسبقاً ويُحسّن الأداء.
     * 
     * @return Container
     */
    public function container(): Container
    {
        if ($this->containerInstance === null) {
            $this->containerInstance = new Container();

            // تسجيل الكائنات كـ Singletons لمنع إعادة إنشائها
            $this->containerInstance->instance(get_class($this->response), $this->response);
            $this->containerInstance->instance(get_class($this->request), $this->request);
            $this->containerInstance->instance(get_class($this), $this);
            $this->containerInstance->instance(Db::class, Db::getInstance());
            $this->containerInstance->bind(QueryBuilder::class, static fn (Container $container): QueryBuilder => Db::getInstance()->query());

            // حقنها في قائمة الـ Args للاستخدامات البديلة وبأسماء مختصرة لدعم الـ Closures بدون Type Hinting
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
     * الحصول على كائن الموجه (Router)
     * 
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * الحصول على كائن الاستجابة (Response)
     * 
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * الحصول على كائن الطلب (Request)
     * 
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * الحصول على المسار الأساسي للمشروع
     * 
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * الحصول على نسخة التطبيق الحالية
     * 
     * @return Application|null
     */
    public static function getInstance(): ?Application
    {
        return self::$instance;
    }

    /********************************************************************************
     * طرق التوجيه المختصرة (Router Proxy Methods)
     *******************************************************************************/

    /**
     * تسجيل مسار من نوع GET
     *
     * @param  string $path مسار الرابط
     * @param  callable|string|array $callback الدالة أو المتحكم المطلوب تنفيذه
     * @return \yurni\Router\Route
     */
    public function get($path, $callback)
    {
        return $this->router->register(["get"], $path, $callback);
    }

    /**
     * تسجيل مسار من نوع POST
     *
     * @param  string $path مسار الرابط
     * @param  callable|string|array $callback الدالة أو المتحكم المطلوب تنفيذه
     * @return \yurni\Router\Route
     */
    public function post($path, $callback)
    {
        return $this->router->register(["post"], $path, $callback);
    }

    /**
     * تسجيل مسار من نوع PATCH
     *
     * @param  string $path مسار الرابط
     * @param  callable|string|array $callback الدالة أو المتحكم المطلوب تنفيذه
     * @return \yurni\Router\Route
     */
    public function patch($path, $callback)
    {
        return $this->router->register(["patch"], $path, $callback);
    }

    /**
     * تسجيل مسار من نوع PUT
     *
     * @param  string $path مسار الرابط
     * @param  callable|string|array $callback الدالة أو المتحكم المطلوب تنفيذه
     * @return \yurni\Router\Route
     */
    public function put($path, $callback)
    {
        return $this->router->register(["put"], $path, $callback);
    }

    /**
     * تسجيل مسار من نوع DELETE
     *
     * @param  string $path مسار الرابط
     * @param  callable|string|array $callback الدالة أو المتحكم المطلوب تنفيذه
     * @return \yurni\Router\Route
     */
    public function delete($path, $callback)
    {
        return $this->router->register(["delete"], $path, $callback);
    }

    /**
     * تسجيل مسار يقبل جميع طرق الطلبات (GET, POST, PUT, DELETE, PATCH)
     *
     * @param  string $path مسار الرابط
     * @param  callable|string|array $callback الدالة أو المتحكم المطلوب تنفيذه
     * @return \yurni\Router\Route
     */
    public function any($path, $callback)
    {
        return $this->router->register(["post", "get", "put", "delete", "patch"], $path, $callback);
    }

    /**
     * تسجيل مسار يقبل طرق محددة فقط يتم تمريرها كمصفوفة
     *
     * @param  array $methods مصفوفة بالطرق المسموحة
     * @param  string $path مسار الرابط
     * @param  callable|string|array $callback الدالة أو المتحكم المطلوب تنفيذه
     * @return \yurni\Router\Route
     */
    public function only($methods = [], $path, $callback)
    {
        return $this->router->register($methods, $path, $callback);
    }

    /**
     * تشغيل التطبيق (معالجة الطلب وإرجاع الاستجابة)
     * هذه الدالة تقوم بحماية الطلبات عبر فحص توكن الـ CSRF، ثم تقوم بحل الرابط وطباعة المخرجات.
     * 
     * @return void|false
     */
    public function run()
    {
        try {
            // التحقق من توكن CSRF تلقائياً لطلبات POST, PUT, DELETE, PATCH
            if (in_array($this->request->getMethod(), ['post', 'put', 'delete', 'patch'])) {
                $token = $this->request->input('csrf_token');
                if (!\yurni\Security\Csrf::validateToken($token)) {
                    http_response_code(403);
                    echo "<h1>403 Forbidden</h1><p>CSRF token validation failed.</p>";
                    return false;
                }
            }

            // حل المسار وطباعة المخرجات النهائية
            echo $this->router->resolve();

        } catch (\Throwable $e) {
            // معالجة الأخطاء الاستثنائية بشكل عام
            error_log("Framework Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());

            http_response_code(500);

            $debug = Config::get('APP_DEBUG', Config::get('app_debug', false));
            $debug = filter_var($debug, FILTER_VALIDATE_BOOLEAN);

            if ($debug) {
                // شاشة أخطاء احترافية مشابهة لـ Ignition / Whoops
                echo "<!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Framework Exception</title>
                    <style>
                        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #1a1b26; color: #a9b1d6; direction: ltr; }
                        .error-header { background-color: #f7768e; padding: 40px; color: #1a1b26; border-bottom: 5px solid #db4b4b; }
                        .error-header h1 { margin: 0; font-size: 32px; font-weight: 700; }
                        .error-header p { margin: 10px 0 0 0; font-size: 18px; font-weight: 500; opacity: 0.9; }
                        .error-container { padding: 40px; max-width: 1200px; margin: 0 auto; }
                        .error-section { background-color: #24283b; border-radius: 8px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); border: 1px solid #414868; }
                        .error-section h3 { margin-top: 0; color: #7aa2f7; font-size: 20px; margin-bottom: 15px; border-bottom: 1px solid #414868; padding-bottom: 10px; }
                        .file-info { background-color: #1f2335; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 16px; color: #e0af68; border-left: 4px solid #ff9e64; margin-bottom: 20px; }
                        pre { background-color: #1f2335; color: #c0caf5; padding: 20px; border-radius: 6px; overflow-x: auto; font-size: 14px; line-height: 1.6; margin: 0; border: 1px solid #292e42; }
                        .line { display: block; }
                        .line:hover { background-color: #292e42; }
                    </style>
                </head>
                <body>
                    <div class='error-header'>
                        <h1>Yurni Exception Occurred</h1>
                        <p>" . htmlspecialchars($e->getMessage()) . "</p>
                    </div>
                    <div class='error-container'>
                        <div class='error-section'>
                            <h3>Exception Location</h3>
                            <div class='file-info'>
                                <strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>
                                <strong>Line:</strong> " . $e->getLine() . "
                            </div>
                        </div>
                        <div class='error-section'>
                            <h3>Stack Trace</h3>
                            <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
                        </div>
                    </div>
                </body>
                </html>";

            } else {
                echo "<h1>500 Internal Server Error</h1>";
                echo "<p>An unexpected error occurred. Please try again later.</p>";
            }
        }
    }
}
