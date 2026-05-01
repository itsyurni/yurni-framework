<?php
namespace yurni\Router;

/**
 * كلاس المسار (Route)
 * يمثل كائناً لمسار واحد يحتوي على رابطه، الدالة المرتبطة به، والمعاملات التي تم التقاطها.
 */
class Route
{
    /**
     * @var callable|array الدالة المرتبطة بالمسار
     */
    protected $callback;

    /**
     * @var array|string طرق الطلب المسموحة (مثل ['get', 'post'])
     */
    protected $method;

    /**
     * @var string الرابط أو التعبير النمطي للرابط
     */
    protected string $uri;

    /**
     * @var string اسم المسار (مفيد لإنشاء روابط بناءً على الاسم)
     */
    protected string $name = '';

    /**
     * @var array المتغيرات (Parameters) الملتقطة من الرابط
     */
    protected array $params = [];

    /**
     * @var array البرمجيات الوسيطة المسجلة لهذا المسار
     */
    protected array $middlewares = [];

    /**
     * إنشاء كائن المسار
     *
     * @param array|string   $method   طريقة الطلب
     * @param string         $uri      مسار الرابط
     * @param callable|array $callback الدالة المراد تنفيذها
     */
    public function __construct($method, string $uri, $callback)
    {
        $this->method   = $method;
        $this->uri      = $uri;
        $this->callback = $callback;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    /**
     * جلب الدالة المرتبطة بالمسار
     *
     * @return callable|array
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * جلب طريقة الطلب
     *
     * @return array|string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * جلب الرابط المسجل
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * جلب اسم المسار
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * جلب متغير محدد أو جميع المتغيرات الملتقطة
     *
     * @param string|null $key اسم المتغير
     * @return mixed مصفوفة المتغيرات كاملة أو القيمة المحددة
     */
    public function getParam(string $key = null)
    {
        if ($key !== null) {
            return $this->params[$key] ?? null;
        }
        return $this->params;
    }

    /**
     * جلب البرمجيات الوسيطة المسجلة
     *
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    // -------------------------------------------------------------------------
    // Setters
    // -------------------------------------------------------------------------

    /**
     * تعيين اسم للمسار الحالي
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * تخزين متغير تم التقاطه من الرابط
     *
     * @param string $key
     * @param mixed  $val
     * @return self
     */
    public function setParam(string $key, $val): self
    {
        $this->params[$key] = $val;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Middleware
    // -------------------------------------------------------------------------

    /**
     * ربط Middleware أو أكثر بهذا المسار
     *
     * @param string|array $middlewares
     * @return self
     */
    public function middleware($middlewares): self
    {
        $this->middlewares = array_merge($this->middlewares, (array) $middlewares);
        return $this;
    }

    /**
     * دعم الاستدعاء الديناميكي للـ Middlewares كطرق مباشرة
     * مثال: ->auth() بدلاً من ->middleware('auth')
     *
     * @param string $method اسم الـ Middleware
     * @param array  $args
     * @return self
     */
    public function __call(string $method, array $args): self
    {
        return $this->middleware($method);
    }

    // -------------------------------------------------------------------------
    // HTTP Method Helpers
    // -------------------------------------------------------------------------

    public function isGet(): bool
    {
        return in_array('get', (array) $this->method, true);
    }

    public function isPost(): bool
    {
        return in_array('post', (array) $this->method, true);
    }

    public function isPut(): bool
    {
        return in_array('put', (array) $this->method, true);
    }

    public function isPatch(): bool
    {
        return in_array('patch', (array) $this->method, true);
    }

    public function isDelete(): bool
    {
        return in_array('delete', (array) $this->method, true);
    }

    public function isAny(): bool
    {
        return in_array('any', (array) $this->method, true);
    }

    public function isOnly(): bool
    {
        return in_array('only', (array) $this->method, true);
    }
}
