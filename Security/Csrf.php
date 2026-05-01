<?php
namespace yurni\Security;

use yurni\Http\Request;

/**
 * كلاس الحماية من هجمات تزوير الطلبات عبر المواقع (CSRF)
 * يقوم بتوليد والتحقق من رموز (Tokens) أمنية لضمان أن الطلبات قادمة من نماذجنا فقط.
 *
 * يمكن استخدامه بطريقتين:
 *  1. كـ Middleware على مسار محدد: ->middleware('csrf')
 *  2. كدوال static مباشرة في القوالب لتوليد الـ Token
 */
class Csrf
{
    // -------------------------------------------------------------------------
    // Middleware
    // -------------------------------------------------------------------------

    /**
     * تنفيذ فحص CSRF عند استخدام الكلاس كـ Middleware
     * يُستدعى تلقائياً عند تسجيله عبر ->middleware('csrf')
     *
     * @param Request $request
     * @return bool
     */
    public function __invoke(Request $request): bool
    {
        return self::validateToken($request->csrfToken());
    }

    // -------------------------------------------------------------------------
    // Token Management
    // -------------------------------------------------------------------------

    /**
     * توليد رمز (Token) CSRF جديد وتخزينه في الجلسة
     * يُولَّد مرة واحدة فقط لكل جلسة
     *
     * @return string
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * تجديد الرمز لتعزيز الأمان بعد كل عملية ناجحة
     *
     * @return string
     */
    public static function refreshToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * التحقق من تطابق الرمز المرسل مع الرمز المحفوظ في الجلسة
     * يستخدم hash_equals لمنع هجمات الـ Timing Attacks
     *
     * @param string|null $token الرمز المرسل من المستخدم
     * @return bool
     */
    public static function validateToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // -------------------------------------------------------------------------
    // HTML Helpers
    // -------------------------------------------------------------------------

    /**
     * توليد حقل إدخال HTML مخفي يحتوي على الـ Token
     * للاستخدام داخل نماذج HTML
     *
     * @return string
     */
    public static function getField(): string
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
