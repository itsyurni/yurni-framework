<?php

use yurni\Config;
use yurni\Security\Csrf;
use yurni\View;

if (!function_exists('env')) {
    /**
     * جلب قيمة من متغيرات البيئة أو القيمة الافتراضية
     */
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    /**
     * جلب قيمة من إعدادات التطبيق
     */
    function config($key, $default = null) {
        return Config::get($key, $default);
    }
}

if (!function_exists('view')) {
    /**
     * معالجة وعرض قالب HTML
     */
    function view($view, $args = []) {
        return View::render($view, $args);
    }
}

if (!function_exists('redirect')) {
    /**
     * إعادة توجيه المستخدم إلى مسار جديد
     */
    function redirect($url) {
        $safeUrl = filter_var($url, FILTER_SANITIZE_URL);
        header("Location: $safeUrl");
        exit;
    }
}

if (!function_exists('csrf_field')) {
    /**
     * توليد حقل الإدخال المخفي لحماية النماذج (CSRF)
     */
    function csrf_field() {
        return Csrf::getField();
    }
}

if (!function_exists('csrf_token')) {
    /**
     * جلب قيمة توكن الحماية الحالي
     */
    function csrf_token() {
        return Csrf::generateToken();
    }
}

if (!function_exists('flash')) {
    /**
     * قراءة أو كتابة رسائل الإشعارات (Flash Messages)
     */
    function flash($key = null, $message = null) {
        $session = new \yurni\Http\Session();
        if (is_null($key)) {
            return $session;
        }
        if (!is_null($message)) {
            $session->setFlash($key, $message);
            return null;
        }
        return $session->getFlash($key);
    }
}

if (!function_exists('dd')) {
    /**
     * طباعة المتغيرات بشكل منسق وإيقاف التنفيذ (Dump and Die)
     */
    function dd(...$vars) {
        echo '<div style="background-color: #18171B; color: #FF8400; padding: 10px; border-radius: 5px; font-family: monospace; direction: ltr; text-align: left;">';
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        echo '</div>';
        exit;
    }
}
