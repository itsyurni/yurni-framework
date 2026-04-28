<?php
namespace yurni\Security;

/**
 * كلاس الحماية من هجمات تزوير الطلبات عبر المواقع (CSRF)
 * يقوم بتوليد والتحقق من رموز (Tokens) أمنية لضمان أن الطلبات قادمة من نماذجنا فقط.
 */
class Csrf {
    
    /**
     * توليد رمز (Token) CSRF جديد وتخزينه في الجلسة (Session)
     *
     * @return string الرمز المولد
     */
    public static function generateToken(): string {
        // التأكد من أن الجلسة تعمل قبل محاولة حفظ الرمز
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // توليد الرمز مرة واحدة فقط لكل جلسة
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * تجديد الرمز (Token) لتعزيز الأمان بعد كل عملية ناجحة
     *
     * @return string الرمز الجديد
     */
    public static function refreshToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * التحقق من تطابق الرمز المرسل مع الرمز المحفوظ في الجلسة
     *
     * @param string|null $token الرمز المرسل من المستخدم
     * @return bool النتيجة
     */
    public static function validateToken(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // إذا كان الرمز مفقوداً سواء في الجلسة أو في الطلب، نرفض العملية
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        // استخدام hash_equals لمنع هجمات الـ Timing Attacks
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * توليد حقل إدخال HTML مخفي يحتوي على الرمز
     * يُستخدم هذا الحقل داخل نماذج الـ HTML
     *
     * @return string كود الـ HTML الخاص بالحقل
     */
    public static function getField(): string {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
