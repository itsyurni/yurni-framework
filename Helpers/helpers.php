<?php

use yurni\Config;
use yurni\Security\Csrf;
use yurni\View;
use yurni\Db;
use yurni\Http\Response;
use yurni\Http\Session;

if (!function_exists('env')) {
    /**
     * جلب قيمة من متغيرات البيئة أو القيمة الافتراضية
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    /**
     * جلب قيمة من إعدادات التطبيق
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('view')) {
    /**
     * معالجة وعرض قالب HTML
     *
     * @param string $view
     * @param array $args
     * @return string
     */
    function view(string $view, array $args = []): string
    {
        return View::render($view, $args);
    }
}

if (!function_exists('redirect')) {
    /**
     * إعادة توجيه المستخدم إلى مسار جديد
     *
     * @param string $url
     * @param int $status
     * @param bool $allowExternal
     * @return never
     */
    function redirect(string $url, int $status = 302, bool $allowExternal = false): never
    {
        (new Response())->redirect($url, $status, $allowExternal);
        exit;
    }
}

if (!function_exists('csrf_field')) {
    /**
     * توليد حقل الإدخال المخفي لحماية النماذج (CSRF)
     *
     * @return string
     */
    function csrf_field(): string
    {
        return Csrf::getField();
    }
}

if (!function_exists('csrf_token')) {
    /**
     * جلب قيمة توكن الحماية الحالي
     *
     * @return string
     */
    function csrf_token(): string
    {
        return Csrf::generateToken();
    }
}

if (!function_exists('db')) {
    /**
     * الحصول على كائن قاعدة البيانات (Db)
     *
     * @return Db
     */
    function db(): Db
    {
        return Db::getInstance();
    }
}

if (!function_exists('session')) {
    /**
     * جلب أو تعيين قيم الجلسة
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function session(?string $key = null, mixed $default = null): mixed
    {
        $session = new Session();

        if ($key === null) {
            return $session;
        }

        if (func_num_args() > 1) {
            $session->set($key, $default);
            return null;
        }

        return $session->get($key) ?? $default;
    }
}

if (!function_exists('flash')) {
    /**
     * قراءة أو كتابة رسائل الإشعارات (Flash Messages)
     *
     * @param string|null $key
     * @param mixed $message
     * @return mixed
     */
    function flash(?string $key = null, mixed $message = null): mixed
    {
        $session = new Session();

        if ($key === null) {
            return $session;
        }

        if ($message !== null) {
            $session->flash($key, $message);
            return null;
        }

        return $session->getFlash($key);
    }
}

if (!function_exists('dd')) {
    /**
     * طباعة المتغيرات بشكل منسق وإيقاف التنفيذ (Dump and Die)
     *
     * @param mixed ...$vars
     * @return never
     */
    function dd(mixed ...$vars): never
    {
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

if (!function_exists('base_path')) {
    /**
     * الحصول على المسار الكامل لمجلد المشروع الأساسي أو ملف بداخله
     *
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        $base = \yurni\Application::getInstance()
            ? \yurni\Application::getInstance()->getBasePath()
            : realpath(__DIR__ . '/../');

        return $base . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}
