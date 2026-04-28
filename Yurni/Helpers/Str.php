<?php

namespace yurni\Helpers;

/**
 * كلاس معالجة النصوص (String Utilities)
 */
class Str {

    /**
     * تحويل النص إلى رابط (Slug)
     * مثال: "Hello World" -> "hello-world"
     */
    public static function slug(string $title, string $separator = '-'): string {
        // تحويل الحروف إلى صغيرة وتغيير المسافات
        $title = mb_strtolower($title, 'UTF-8');
        $title = preg_replace('/[^\p{L}\p{Nd}]+/u', $separator, $title);
        $title = trim($title, $separator);
        return $title;
    }

    /**
     * توليد نص عشوائي آمن
     */
    public static function random(int $length = 16): string {
        $string = '';
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        return $string;
    }

    /**
     * قص النص الطويل ووضع "..." في نهايته
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }
        return rtrim(mb_substr($value, 0, $limit, 'UTF-8')) . $end;
    }

    /**
     * التحقق مما إذا كان النص يحتوي على كلمة أو جملة معينة
     */
    public static function contains(string $haystack, $needles): bool {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * التحقق مما إذا كان النص يبدأ بكلمة معينة
     */
    public static function startsWith(string $haystack, $needles): bool {
        foreach ((array) $needles as $needle) {
            if ((string) $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * التحقق مما إذا كان النص ينتهي بكلمة معينة
     */
    public static function endsWith(string $haystack, $needles): bool {
        foreach ((array) $needles as $needle) {
            if (
                $needle !== '' && $needle !== null &&
                substr($haystack, -strlen($needle)) === (string) $needle
            ) {
                return true;
            }
        }
        return false;
    }
}
