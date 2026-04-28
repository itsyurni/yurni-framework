<?php
namespace yurni;

use yurni\View\Template;

/**
 * واجهة محرك القوالب (View Facade)
 * توفر دالة ثابتة ومختصرة لاستدعاء وتصيير (Rendering) ملفات القوالب.
 */
class View
{
    /**
     * @var Template|null كائن محرك القوالب (Singleton) — يُنشأ مرة واحدة فقط طوال الطلب
     */
    private static ?Template $engine = null;

    /**
     * إرجاع كائن محرك القوالب، مع إنشائه إن لم يكن موجوداً (Singleton)
     *
     * @return Template
     */
    private static function getEngine(): Template
    {
        if (self::$engine === null) {
            self::$engine = new Template([
                'temp_path'  => Config::get('views_path',       __DIR__ . '/../app/views/'),
                'cache_path' => Config::get('views_cache_path', __DIR__ . '/../app/views/cache/'),
                'cache'      => Config::get('view_cache',       false),
                'optimize'   => Config::get('view_optimize',    false),
            ]);
        }

        return self::$engine;
    }

    /**
     * تصيير (Render) القالب المطلوب وإرجاع كود الـ HTML
     *
     * @param string $view اسم القالب بدون امتداد .php
     * @param array  $args البيانات التي سيتم تمريرها للقالب
     * @return string كود الـ HTML النهائي
     */
    public static function render($view, $args = []): string
    {
        return self::getEngine()->render($view, $args);
    }

    /**
     * مسح ذاكرة التخزين المؤقت للقوالب
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::getEngine()->clearCache();
    }
}
