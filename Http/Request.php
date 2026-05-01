<?php
namespace yurni\Http;

use yurni\Application;
use yurni\Router\Route;


/**
 * كلاس الطلب (Request)
 * يقوم بتجريد (Abstract) جميع معلومات الطلب القادم من المستخدم مثل المدخلات، الملفات المرفوعة، والـ Headers.
 */
class Request
{

    /**
     * @var Application كائن التطبيق الأساسي
     */
    protected Application $app;

    /**
     * @var array مصفوفة الملفات المرفوعة ($_FILES)
     */
    public $files;

    /**
     * @var array مصفوفة متغيرات السيرفر ($_SERVER)
     */
    protected $_server;

    /**
     * @var Route كائن المسار الحالي المطابق للطلب
     */
    protected Route $route;

    /**
     * @var array|null كاش للمدخلات لمنع إعادة معالجتها أكثر من مرة
     */
    protected ?array $inputCache = null;

    /**
     * منشئ الكلاس
     * يقوم بحفظ بيانات $_SERVER و $_FILES
     * 
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->_server = $_SERVER;
        $this->files = $_FILES;
    }

    /**
     * تعيين كائن المسار المطابق للطلب (يتم تعيينه من داخل Router)
     * 
     * @param Route $route
     * @return self
     */
    public function setRoute(Route $route)
    {
        $this->route = $route;
        return $this;
    }

    /**
     * الحصول على كائن المسار المطابق الحالي
     * 
     * @return Route
     */
    public function route()
    {
        return $this->route;
    }

    /**
     * الحصول على كائن الجلسة (Session)
     * 
     * @return Session
     */
    public function getSession()
    {
        return new Session();
    }

    /**
     * الحصول على قيمة من مصفوفة $_SERVER
     * 
     * @param string $val مفتاح المتغير المطلوب
     * @return mixed القيمة أو false إذا لم يوجد
     */
    public function server($val)
    {
        return isset($this->_server[$val]) ? $this->_server[$val] : false;
    }

    /**
     * الحصول على قيمة من الـ Headers
     * 
     * @param string $key اسم الـ Header
     * @param mixed $default القيمة الافتراضية
     * @return mixed
     */
    public function header(string $key, $default = null)
    {
        $headerKey = str_replace('-', '_', strtoupper($key));

        // التحقق من الصيغة القياسية (HTTP_NAME)
        if (isset($this->_server['HTTP_' . $headerKey])) {
            return $this->_server['HTTP_' . $headerKey];
        }

        // التحقق من الحالات الخاصة مثل CONTENT_TYPE و CONTENT_LENGTH
        if (isset($this->_server[$headerKey])) {
            return $this->_server[$headerKey];
        }

        return $default;
    }

    /**
     * الحصول على مسار الطلب (Path) بعد تنظيفه وإزالة المتغيرات (Query Strings)
     * 
     * @return string المسار الحالي (مثال: /home)
     */
    public function getPath()
    {
        $path_info = $this->server("PATH_INFO");
        if (!$path_info) {
            $request_uri = $this->server('REQUEST_URI');
            if ($request_uri) {
                $path_info = parse_url($request_uri, PHP_URL_PATH);
            } else {
                $path_info = '/';
            }
        }
        return '/' . trim(urldecode($path_info), '/');
    }

    /**
     * الحصول على ملف مرفوع مفرد ومعالجته
     * 
     * @param string $key اسم حقل الإدخال
     * @return FileUpload|null كائن FileUpload أو null
     */
    public function file($key)
    {
        $_file = $this->files[$key] ?? null;
        return $this->hasFile($key) ? $this->makeUploader($_file) : NULL;
    }

    /**
     * الحصول على ملفات مرفوعة متعددة ومعالجتها
     * 
     * @param string $key اسم حقل الإدخال المتعدد
     * @return FileUpload[] مصفوفة من كائنات FileUpload
     */
    public function multiFiles($key)
    {
        if (!$this->hasMultiFiles($key))
            return array();

        $input_files = array();
        $files = $this->files[$key];

        $names = $files["name"];
        $types = $files["type"];
        $temps = $files["tmp_name"];
        $errors = $files["error"];
        $sizes = $files["size"];

        foreach ($temps as $i => $tmp) {
            if (empty($tmp) || !is_uploaded_file($tmp))
                continue;

            $_file = array(
                'name' => $names[$i],
                'type' => $types[$i],
                'tmp_name' => $tmp,
                'error' => $errors[$i],
                'size' => $sizes[$i]
            );

            $input_files[] = $this->makeUploader($_file);
        }

        return $input_files;
    }

    /**
     * التحقق من وجود ملف مرفوع مفرد
     * 
     * @param string $key
     * @return bool
     */
    public function hasFile($key)
    {
        $file = $this->files[$key] ?? false;
        if (!$file)
            return FALSE;

        $tmp = $file["tmp_name"];
        if (!is_string($tmp))
            return FALSE;

        return is_uploaded_file($tmp);
    }

    /**
     * التحقق من وجود ملفات مرفوعة متعددة
     * 
     * @param string $key
     * @return bool
     */
    public function hasMultiFiles($key)
    {
        $files = $this->files[$key] ?? false;
        if (!$files)
            return FALSE;

        $uploaded_files = $files["tmp_name"];
        if (!is_array($uploaded_files))
            return FALSE;

        foreach ($uploaded_files as $tmp_file) {
            if (!empty($tmp_file) && is_uploaded_file($tmp_file))
                return TRUE;
        }

        return FALSE;
    }

    /**
     * إنشاء كائن لرفع الملف
     * 
     * @param array $_file
     * @return FileUpload
     */
    protected function makeUploader(array $_file)
    {
        return new FileUpload($_file);
    }

    /**
     * الحصول على طريقة الطلب الحالية (GET, POST, ...)
     * 
     * @return string
     */
    public function getMethod()
    {
        return strtolower($this->server('REQUEST_METHOD'));
    }

    // -------------------------------------------------------------------------
    // HTTP Method Helpers
    // هذه الدوال تصف الطلب الحالي القادم من المستخدم.
    // السؤال هنا: "ما نوع هذا الطلب؟" — وهو سؤال Request وليس Route.
    // للتحقق مما يقبله مسار معين استخدم: $route->acceptsMethod('post')
    // -------------------------------------------------------------------------

    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtolower($method);
    }

    public function isPost(): bool
    {
        return $this->isMethod('post');
    }

    public function isGet(): bool
    {
        return $this->isMethod('get');
    }

    public function isPut(): bool
    {
        return $this->isMethod('put');
    }

    public function isPatch(): bool
    {
        return $this->isMethod('patch');
    }

    public function isDelete(): bool
    {
        return $this->isMethod('delete');
    }

    public function isHttps()
    {
        return $this->server('HTTPS') ? true : false;
    }

    public function isHttp()
    {
        return !$this->isHttps();
    }

    public function isAjax()
    {
        return (!empty($this->server('HTTP_X_REQUESTED_WITH')) && strtolower($this->server('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest');
    }

    /**
     * جلب جسم الطلب الأصلي (Raw Body)
     * يفيد في حالة استقبال بيانات JSON
     * 
     * @return string|false
     */
    public function body()
    {
        return file_get_contents("php://input");
    }

    /**
     * استخراج جميع المدخلات المرسلة في الطلب — بيانات خام كما أرسلها المستخدم.
     *
     * القرار التصميمي:
     *   inputs() تقرأ البيانات فقط، ولا تنقّيها.
     *   التنقية (Sanitization) والتحقق (Validation) مسؤولية طبقة أعلى (Validator).
     *   تطبيق FILTER_SANITIZE_SPECIAL_CHARS هنا يسبب double-escaping إذا طبّق
     *   المستخدم أي تنقية لاحقاً، ويغيّر القيم قبل وصولها لطبقة الأعمال.
     *
     * @return array مصفوفة المدخلات الخام
     */
    public function inputs(): array
    {
        if ($this->inputCache !== null) {
            return $this->inputCache;
        }

        $body = [];

        // ── query string (?key=val) — متاح في جميع أنواع الطلبات ──────────
        // isset($_GET) شبه دائم الصدق (true حتى لو فارغاً)، لذا نتحقق من المحتوى.
        if (!empty($_GET)) {
            foreach ($_GET as $key => $val) {
                $body[$key] = $val;
            }
        }

        // ── POST form-encoded / multipart ──────────────────────────────────
        if ($this->isMethod('post') && !empty($_POST)) {
            foreach ($_POST as $key => $val) {
                $body[$key] = $val;
            }
        }

        // ── Raw body: JSON أو urlencoded لـ PUT / PATCH / DELETE / POST ────
        if ($this->isMethod('post') || $this->isMethod('put') || $this->isMethod('delete') || $this->isMethod('patch')) {
            $raw_body = $this->body();
            $content_type = $this->header('Content-Type') ?: '';

            if ($raw_body !== '' && strpos($content_type, 'application/json') !== false) {
                $decoded = json_decode($raw_body, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // JSON يحل محل أي قيمة بنفس المفتاح من $_POST
                    $body = array_merge($body, $decoded);
                }
            } elseif ($raw_body !== '' && !$this->isMethod('post')) {
                // PUT / PATCH / DELETE urlencoded
                parse_str($raw_body, $parsed_vars);
                if (is_array($parsed_vars)) {
                    $body = array_merge($body, $parsed_vars);
                }
            }
        }

        $this->inputCache = $body;
        return $this->inputCache;
    }

    /**
     * جلب مدخل محدد عن طريق المفتاح
     * 
     * @param string $key اسم الحقل
     * @return mixed القيمة أو null
     */
    public function input($key)
    {
        return $this->inputs()[$key] ?? null;
    }

    /**
     * خاصية سحرية تتيح جلب المتغيرات كأنها خصائص
     * مثال: $request->username بدل $request->input('username')
     */
    public function __get($key)
    {
        return $this->input($key) ?? null;
    }


    /**
     * الحصول على توكن CSRF من الطلب (Input أو Headers)
     * 
     * @return string|null
     */
    public function csrfToken()
    {
        return $this->input('csrf_token')
            ?: $this->header('X-CSRF-TOKEN')
            ?: $this->header('X-XSRF-TOKEN');
    }

    /**
     * التحقق من المدخلات باستخدام Validator
     * يعيد البيانات المتحقق منها إذا نجح، أو يقوم بإعادة التوجيه للخلف مع رسائل الأخطاء.
     *
     * @param array $rules قواعد التحقق
     * @param array $messages رسائل الأخطاء المخصصة (اختياري)
     * @return array البيانات التي تم التحقق منها
     */
    public function validate(array $rules, array $messages = []): array
    {
        $validator = \yurni\Helpers\Validator::make($this->inputs(), $rules, $messages);

        if ($validator->fails()) {
            $session = $this->getSession();
            $session->flash('errors', $validator->errors());
            $session->flash('old', $this->inputs());

            $referer = $this->header('referer', '/');
            header("Location: $referer");
            exit;
        }

        $validatedData = [];
        foreach (array_keys($rules) as $field) {
            // Support dot notation if needed in future, currently just plain keys
            if ($this->input($field) !== null) {
                $validatedData[$field] = $this->input($field);
            }
        }

        return $validatedData;
    }
}
