<?php

declare(strict_types=1);

namespace yurni;

use yurni\Http\Request;
use yurni\Http\Response;
use yurni\Database\QueryBuilder;

/**
 * الكلاس الأساسي للمتحكمات (Base Controller)
 * يوفّر الوصول إلى التطبيق وطبقة Query Builder بشكل مباشر داخل المتحكم.
 */
class Controller {
    
    /**
     * @var Application كائن التطبيق الأساسي
     */
    public Application $app;

    /**
     * @var Request كائن الطلب الحالي
     */
    public Request $request;

    /**
     * @var Response كائن الاستجابة
     */
    public Response $response;

    /**
     * @var Db كائن الاتصال بقاعدة البيانات
     */
    protected Db $db;

    /**
     * منشئ الكلاس (Constructor)
     * يتم حقن كائن التطبيق تلقائياً عبر حاوية الـ Dependency Injection
     * 
     * @param Application $app
     */
    public function __construct(Application $app)
    {
       $this->app = $app;
       $this->db = Db::getInstance();
       $this->request = $this->app->request;
       $this->response = $this->app->response;
    }

    public function db(): Db
    {
        return $this->db;
    }

    public function query(): QueryBuilder
    {
        return $this->db->query();
    }

    public function table(string $table): QueryBuilder
    {
        return $this->db->table($table);
    }

    public function transaction(callable $callback): mixed
    {
        return $this->db->transaction($callback);
    }

    /**
     * معالجة وعرض قالب HTML
     * تقوم بدمج البيانات الممررة مع المتغيرات الأساسية (مثل معلومات المسار) وتمريرها لمحرك القوالب.
     * 
     * @param string $view اسم القالب (مثال: 'home' أو 'users.index')
     * @param array $args مصفوفة البيانات التي سيتم عرضها في القالب
     * @return string كود الـ HTML النهائي
     */
    public function render(string $view, array $args = []): string
    {
        // إضافة معلومات المسار الحالي (Route) للمتغيرات المتاحة في القالب
        $this->app->setViewAttr([
            "route" => $this->request->route()
        ]);
        
        // دمج البيانات الممررة من المستخدم مع المتغيرات العامة
        $this->app->setViewAttr($args);
  
        // معالجة القالب وإرجاع الكود
        return View::render($view, $this->app->getViewAttr());
    }
}
