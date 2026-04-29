<?php
namespace yurni;

/**
 * كلاس إدارة الإعدادات (Config Management)
 * يوفر واجهة موحدة لجلب وتخزين الإعدادات من وإلى التطبيق أو بيئة التشغيل (.env).
 */
class Config {
    
    /**
     * @var array مصفوفة الإعدادات المحملة
     */
    protected static array $config = [];

    /**
     * تحميل مصفوفة من الإعدادات ودمجها مع الإعدادات الحالية
     *
     * @param array $config مصفوفة الإعدادات المراد تحميلها
     */
    public static function load(array $config): void {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * جلب قيمة إعداد معين بناءً على المفتاح (Key)
     * يبحث أولاً في المصفوفة الداخلية، ثم في متغيرات البيئة $_ENV.
     *
     * @param string $key مفتاح الإعداد (اسم المتغير)
     * @param mixed $default القيمة الافتراضية في حال عدم العثور على الإعداد
     * @return mixed قيمة الإعداد
     */
    public static function get(string $key, $default = null) {
        // البحث في المصفوفة المحملة مسبقاً
        if (array_key_exists($key, self::$config)) {
            return self::$config[$key];
        }

        // البحث في متغيرات البيئة (Environment Variables)
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        // إرجاع القيمة الافتراضية إذا لم يتم العثور عليه
        return $default;
    }

    /**
     * حفظ أو تحديث إعداد معين في الذاكرة أثناء التشغيل
     *
     * @param string $key مفتاح الإعداد
     * @param mixed $value قيمة الإعداد
     */
    public static function set(string $key, $value): void {
        self::$config[$key] = $value;
    }
}
