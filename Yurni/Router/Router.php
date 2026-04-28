<?php
namespace yurni\Router;

use yurni\Application;
use yurni\Http\Response;
use yurni\Http\Request;
use yurni\Exception\NotFoundException;

/**
 * كلاس الموجه (Router)
 * مسؤول عن تسجيل ومعالجة الروابط (Routes) وتوجيهها للمتحكمات (Controllers) المناسبة.
 */
class Router {

    /**
     * @var Application كائن التطبيق الأساسي
     */
    protected Application $app;

    /**
     * @var array مصفوفة تحتوي على كل المسارات المسجلة
     */
    protected array $routes = [];

    /**
     * @var Request كائن الطلب
     */
    protected Request $request;

    /**
     * @var Response كائن الاستجابة
     */
    protected Response $response;

    /**
     * @var array مصفوفة دوال معالجة الأخطاء (مثل 404)
     */
    protected array $handle = [];

    /**
     * @var array تعبيرات نمطية جاهزة لمعالجة روابط معينة (غير مستخدمة كلياً حالياً ولكن يمكن تفعيلها)
     */
    protected $patterns = [
        ':all' => '(.*)',
        ':id' => '(\d+)',
        ':int' => '(\d+)',
        ':number' => '([+-]?([0-9]*[.])?[0-9]+)',
        ':float' => '([+-]?([0-9]*[.])?[0-9]+)',
        ':bool' => '(true|false|1|0)',
        ':string' => '([\w\-_]+)',
        ':slug' => '([\w\-_]+)',
        ':uuid' => '([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})',
        ':date' => '([0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]))',
    ];
    
    /**
     * منشئ الكلاس (Constructor)
     * 
     * @param Application $app
     */
    public function __construct(Application $app) 
    {
        $this->app = $app;
        $this->response = $this->app->response;
        $this->request = $this->app->request;
        
        // إعداد استجابة افتراضية في حالة عدم العثور على المسار (404)
        $this->handle("404", function() {
            http_response_code(404);
            echo "<h1>404 Not Found</h1><p>The requested URL was not found on this server.</p>";
        });
    }

    /**
     * تسجيل دالة مخصصة لمعالجة أخطاء معينة (مثل 404 أو 500)
     * 
     * @param string $type نوع الخطأ
     * @param callable $callback الدالة المراد تنفيذها
     */
    public function handle($type, $callback) {
        return $this->handle[$type] = $callback;
    }
    
    /**
     * إضافة تعبيرات نمطية مخصصة للروابط
     * 
     * @param array $patterns
     * @return self
     */
    public function setPattern($patterns) {
        foreach($patterns as $key => $val){
            $this->patterns[$key] = $val;
        } 
        return $this;
    }

    /**
     * تنفيذ دالة معالجة الأخطاء المحفوظة
     * 
     * @param string $type
     * @return mixed
     */
    public function getHandle($type) {
        return $this->app->container()->call($this->handle[$type]);
    }

    /**
     * تحويل رابط المسار إلى تعبير نمطي (Regex) للبحث والمطابقة
     * يقوم بتحويل {param} إلى مجموعة تلتقط الاسم والقيمة
     * 
     * @param string $route المسار المراد تحويله
     * @return string التعبير النمطي الناتج
     */
    public function routeToRegex($route)
    {
        // تهيئة الـ Slashes
        $route = preg_replace("/\\//", "\/", $route);
        
        // تحويل المتغيرات في الرابط مثل {id} لتلتقط قيمتها في مصفوفة المطابقات
        $route = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function($matches) {
            return '(?P<' . $matches[1] . '>[^\/]+)';
        }, $route);
        
        // إغلاق التعبير النمطي
        $route = "/^" . $route . "$/i";
        return $route;
    }

    /**
     * الحصول على جميع المسارات المسجلة
     * 
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * تسجيل مسار جديد وحفظه
     * 
     * @param array $method طرق الطلب (مثل GET, POST)
     * @param string $uri الرابط 
     * @param callable|array $action الدالة أو المتحكم المطلوب
     * @return Route
     */
    public function register($method, $uri, $action)
    {
        $routeUri = $this->routeToRegex($uri);
        $route = new Route($method, $routeUri, $action); 
        $this->routes[] = $route;
        return $route;
    }

    /**
     * البحث عن المسار المطابق للطلب الحالي (رابط + طريقة الطلب)
     * 
     * @param string $path رابط الطلب الفعلي
     * @param string $method طريقة الطلب الفعلية (مثل GET)
     * @return Route|false كائن المسار أو false في حال عدم وجوده
     */
    protected function findRoute($path, $method)
    {
        foreach($this->getRoutes() as $route)
        {
            // مطابقة الرابط بالتعبير النمطي الخاص بالمسار
            if(preg_match($route->getUri(), $path, $matches) && in_array($method, $route->getMethod()))
            {
                // إذا تم العثور على المسار، قم بتخزين المتغيرات الملتقطة داخل كائن المسار
                foreach($matches as $key => $val)
                { 
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
     * @param callable|array $callback الدالة المراد تنفيذها
     * @param array $args المعاملات الملتقطة من الرابط
     * @return mixed
     */
    public function resolveCallback($callback, $args) {
        $output = $this->app->container()->injectArgs($args)->call($callback);
        
        // إذا كان المخرج هو كائن Response بالفعل (مثل دالة redirect)، نرجعه مباشرة
        if ($output instanceof Response) {
            return $output->body();
        }
        
        // التحويل التلقائي للمصفوفات إلى JSON
        if(is_array($output)) {
            return $this->response->json($output)->body();
        } else {
            return $this->response->html($output)->body();
        }
    }

    /**
     * تحليل الطلب الحالي ومحاولة العثور على المسار وتنفيذه
     * 
     * @return mixed النتيجة أو دالة الخطأ 404
     */
    public function resolve()
    {
        $request_url = $this->request->getPath();
        $request_method = $this->request->getMethod();
        
        // محاولة العثور على المسار المطلوب
        $route = $this->findRoute($request_url, $request_method);

        if($route)
        {
            // حفظ كائن المسار المكتشف داخل كائن الطلب لتسهيل الوصول إليه
            $this->request->setRoute($route);

            // جلب دالة المسار والمتغيرات الممررة في الرابط
            $routeCallback = $route->getCallback();
            $routeArgs = $route->getParam(); // يرجع مصفوفة المتغيرات

            // فحص وتشغيل الـ Middlewares المسجلة على هذا المسار
            foreach ($route->getMiddlewares() as $key) {
                // إذا فشل الـ Middleware، نوقف التنفيذ
                if (!$this->app->getMiddleware($key)) {
                    // إذا لم يقم الـ Middleware بإرسال استجابة (مثل Redirect)، نرسل 403 كافتراضي
                    if (http_response_code() === 200) {
                        http_response_code(403);
                        echo "<h1>403 Forbidden</h1><p>Access Denied.</p>";
                    }
                    return false;
                }
            }
            
            // تنفيذ الدالة المسؤولة عن المسار
            return $this->resolveCallback($routeCallback, $routeArgs);
        } else {
            // تنفيذ دالة 404 في حال لم يتم إيجاد مسار
            return $this->getHandle("404");
        }
    }
}
