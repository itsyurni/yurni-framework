<?php
namespace yurni\View;

/**
 * محرك القوالب (Template Engine)
 * مسؤول عن تجميع قوالب HTML وتحويل الأكواد المختصرة (مثل {% extends %}, {{ var }}) إلى كود PHP صالح للتنفيذ.
 */
class Template {

    /**
     * @var array مصفوفة الكتل البرمجية (Blocks) المحفوظة أثناء المعالجة
     */
    private $blocks = [];

    /**
     * @var string مسار مجلد القوالب الأساسية
     */
    private $temp_path;

    /**
     * @var string مسار مجلد الكاش (Cache)
     */
    private $cache_path;

    /**
     * @var bool حالة تشغيل أو إيقاف التخزين المؤقت (Cache)
     */
    private $cache_enabled;

    /**
     * @var bool حالة تحسين الكود وإزالة الفراغات الإضافية
     */
    private $optimize;

    /**
     * منشئ الكلاس
     *
     * @param array $data مصفوفة الإعدادات (المسارات، الكاش، والتحسين)
     */
    public function __construct($data = []) {
        $this->temp_path    = rtrim($data['temp_path'],  '/\\') . DIRECTORY_SEPARATOR;
        $this->cache_path   = rtrim($data['cache_path'], '/\\') . DIRECTORY_SEPARATOR;
        $this->cache_enabled = $data['cache']    ?? false;
        $this->optimize      = $data['optimize'] ?? false;
    }

    // =========================================================================
    //  Public API
    // =========================================================================

    /**
     * معالجة القالب وعرضه
     *
     * @param string $file   اسم الملف (بدون امتداد أو معه)
     * @param array  $params البيانات الممررة للقالب
     * @return string كود HTML النهائي بعد المعالجة
     */
    public function render($file, $params = []) {
        // إعادة تهيئة الكتل في كل استدعاء لمنع تسرّب بيانات بين الصفحات
        $this->blocks = [];

        $cached_file = $this->cache($file);

        // استخراج المتغيرات لتصبح متاحة داخل القالب
        foreach ($params as $key => $value) {
            $$key = $value;
        }

        // قراءة ناتج التنفيذ (Output Buffering)
        ob_start();
        require $cached_file;
        return ob_get_clean();
    }

    /**
     * مسح كل ملفات الكاش الموجودة
     */
    public function clearCache() {
        foreach (glob($this->cache_path . '*.php') as $file) {
            unlink($file);
        }
    }

    // =========================================================================
    //  Caching
    // =========================================================================

    /**
     * فحص وتجميع وحفظ القالب (Caching System)
     *
     * @param string $file اسم الملف
     * @return string مسار الملف المجمع
     */
    public function cache($file) {
        // إنشاء مجلد الكاش إن لم يكن موجوداً
        if (!is_dir($this->cache_path)) {
            mkdir($this->cache_path, 0744, true);
        }

        $file_with_ext = $this->ensureExtension($file);
        $source_file   = $this->temp_path . $file_with_ext;

        // استخدام md5 لتشفير اسم الملف وتجنب أخطاء المسارات الطويلة على Windows
        $cached_file = $this->cache_path . md5($source_file) . '.php';

        // إعادة التجميع إذا: الكاش مُعطَّل، أو الملف غير موجود، أو المصدر تغيَّر
        $needsCompile = !$this->cache_enabled
            || !file_exists($cached_file)
            || filemtime($cached_file) < filemtime($source_file);

        if ($needsCompile) {
            $code = $this->includeFile($file);

            if ($this->optimize) {
                $code = $this->minify($code);
            }

            $code = $this->compiler($code);

            file_put_contents(
                $cached_file,
                '<?php class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $code
            );
        }

        return $cached_file;
    }

    // =========================================================================
    //  File Inclusion & Inheritance
    // =========================================================================

    /**
     * جلب محتويات الملف ومعالجة الوراثة والتضمين ({% extends %} و {% include %})
     *
     * @param string $file
     * @return string الكود المدمج
     * @throws \RuntimeException إذا حاول أحدهم الوصول لمسار خارج مجلد القوالب
     */
    public function includeFile($file) {
        $file = trim($file, "'\"");
        $file_with_ext = $this->ensureExtension($file);

        // ——— الحماية من ثغرة Path Traversal ———
        $realBase = realpath($this->temp_path);
        $realFile = realpath($this->temp_path . $file_with_ext);

        if ($realBase === false) {
            throw new \RuntimeException("مجلد القوالب غير موجود: {$this->temp_path}");
        }

        if ($realFile === false || strpos($realFile, $realBase . DIRECTORY_SEPARATOR) !== 0) {
            throw new \RuntimeException("وصول مرفوض: محاولة الوصول لملف خارج مجلد القوالب — '{$file}'");
        }
        // ——————————————————————————————————————

        $code = file_get_contents($realFile);

        // البحث عن extends و include واستبدالها بمحتويات الملف (بشكل متداخل Recursive)
        if (preg_match_all('/\{%\s*(extends|include)\s*(.+?)\s*\%}/is', $code, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $value) {
                $code = str_replace($value[0], $this->includeFile($value[2]), $code);
            }
        }

        // حذف أي علامات extends/include تبقّت
        $code = preg_replace('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', '', $code);

        return $code;
    }

    // =========================================================================
    //  Compiler Pipeline
    // =========================================================================

    /**
     * مترجم القوالب الشامل (Compiler)
     *
     * @param string $output
     * @return string الكود النهائي
     */
    public function compiler($output) {
        $output = $this->compileVerbatim($output);   // حماية الكتل الخام أولاً
        $output = $this->compileBlock($output);
        $output = $this->compileYield($output);
        $output = $this->compilePHP($output);
        $output = $this->restoreVerbatim($output);   // استعادة الكتل الخام
        return $output;
    }

    /**
     * تحويل العلامات المختصرة إلى أكواد PHP فعلية
     *
     * @param string $output
     * @return string
     */
    public function compilePHP($output) {
        // الهروب الآمن للنصوص: {{{ var }}} => htmlspecialchars($var)
        $output = preg_replace(
            '/\{{{\s*(.+?)\s*\}}}/is',
            "<?php echo htmlspecialchars($1, ENT_QUOTES, 'UTF-8') ?>",
            $output
        );

        // الطباعة العادية: {{ var }} => echo $var
        $output = preg_replace(
            '/\{{\s*(.+?)\s*\}}/is',
            '<?php echo $1; ?>',
            $output
        );

        // حلقة تكرار مختصرة: {% each $arr as $item %} ... {% endeach %}
        $output = preg_replace(
            '/\{%\s*each\s*(.*?)\s+as\s+(.*?)\s*%\}(.*?)\{%\s*endeach\s*%\}/is',
            '<?php foreach($1 as $2){ ?> $3 <?php } ?>',
            $output
        );

        // أوامر PHP العادية: {% ... %}
        $output = preg_replace('/\{%\s*(.+?)\s*\%}/is', '<?php $1 ?>', $output);

        return $output;
    }

    /**
     * التقاط وحفظ محتويات الكتل (Blocks) لإعادة استخدامها في الـ Yield
     *
     * @param string $code
     * @return string
     */
    public function compileBlock($code) {
        if (preg_match_all(
            '/\{%\s*block\s+(?P<blockName>\S+?)\s*%\}(?P<blockContent>.*?)\{%\s*endblock\s*%\}/is',
            $code,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $value) {
                $name    = $value['blockName'];
                $content = $value['blockContent'];

                if (!isset($this->blocks[$name])) {
                    $this->blocks[$name] = '';
                }

                if (strpos($content, '@parent') === false) {
                    $this->blocks[$name] = $content;
                } else {
                    // دمج الكود الجديد مع كود الأب في حال وجود @parent
                    $this->blocks[$name] = str_replace('@parent', $this->blocks[$name], $content);
                }

                // إزالة الكتلة من مكانها، ستُوضع لاحقاً في مكان الـ yield
                $code = str_replace($value[0], '', $code);
            }
        }

        return $code;
    }

    /**
     * وضع الكتل البرمجية في أماكنها الصحيحة ({% yield blockName %})
     *
     * @param string $code
     * @return string
     */
    public function compileYield($code) {
        foreach ($this->blocks as $block => $value) {
            $code = preg_replace('/\{%\s*yield\s+' . preg_quote($block, '/') . '\s*%\}/', $value, $code);
        }

        // إزالة الـ yields الفارغة التي لم يتم تعبئتها
        $code = preg_replace('/\{%\s*yield\s+.*?\s*%\}/i', '', $code);

        return $code;
    }

    // =========================================================================
    //  Verbatim (Raw Block) — لحماية كود Vue / Alpine / إلخ
    // =========================================================================

    /** @var array مؤقت لحفظ محتويات {% verbatim %} */
    private $verbatimBlocks = [];

    /**
     * استخراج كتل {% verbatim %} وحفظها مؤقتاً لمنع معالجتها
     *
     * @param string $code
     * @return string
     */
    private function compileVerbatim($code) {
        $this->verbatimBlocks = [];

        return preg_replace_callback(
            '/\{%\s*verbatim\s*%\}(.*?)\{%\s*endverbatim\s*%\}/is',
            function ($matches) {
                $placeholder = '__VERBATIM_' . count($this->verbatimBlocks) . '__';
                $this->verbatimBlocks[$placeholder] = $matches[1];
                return $placeholder;
            },
            $code
        );
    }

    /**
     * استعادة محتويات {% verbatim %} بعد انتهاء التجميع
     *
     * @param string $code
     * @return string
     */
    private function restoreVerbatim($code) {
        foreach ($this->verbatimBlocks as $placeholder => $content) {
            $code = str_replace($placeholder, $content, $code);
        }
        return $code;
    }

    // =========================================================================
    //  Helpers
    // =========================================================================

    /**
     * إضافة امتداد .php إذا لم يكن موجوداً
     *
     * @param string $file
     * @return string
     */
    private function ensureExtension($file) {
        return str_ends_with($file, '.php') ? $file : $file . '.php';
    }

    /**
     * تحسين الكود HTML مع حماية العناصر الحساسة (pre, textarea, script)
     *
     * @param string $code
     * @return string
     */
    private function minify($code) {
        // حفظ العناصر الحساسة مؤقتاً قبل الضغط
        $preserved = [];

        $code = preg_replace_callback(
            '/<(pre|textarea|script)\b[^>]*>.*?<\/\1>/is',
            function ($matches) use (&$preserved) {
                $key = '__PRESERVED_' . count($preserved) . '__';
                $preserved[$key] = $matches[0];
                return $key;
            },
            $code
        );

        // إزالة المسافات الزائدة بأمان
        $code = trim(preg_replace('/[ \t]+/', ' ', $code));
        $code = preg_replace('/\n\s*\n/', "\n", $code);

        // استعادة العناصر المحفوظة
        foreach ($preserved as $key => $value) {
            $code = str_replace($key, $value, $code);
        }

        return $code;
    }
}
