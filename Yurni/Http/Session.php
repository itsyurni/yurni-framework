<?php
namespace yurni\Http;

/**
 * كلاس إدارة الجلسات (Session Management)
 * يوفر واجهة سهلة للتعامل مع الـ $_SESSION ويشمل دعماً لرسائل الـ Flash (رسائل لمرة واحدة).
 */
class Session
{
    /**
     * مفتاح التخزين المخصص لرسائل الـ Flash
     */
    protected const FLASH_KEY = 'flash_messages';

    /**
     * منشئ الكلاس
     * يقوم بتشغيل الجلسة إذا لم تكن تعمل، ويهيئ رسائل الـ Flash للحذف في الطلب القادم.
     */
    public function __construct()
    {
        // تشغيل الجلسة بأمان
        if ( $this->is_session_started() === FALSE ) {
            session_start();
        }
    
        // تعليم كافة رسائل الفلاش الحالية ليتم حذفها لاحقاً
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => &$flashMessage) {
            $flashMessage['remove'] = true;
        }
        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }

    /**
     * إنشاء رسالة فلاش (Flash Message) لمرة واحدة فقط
     * ستبقى متاحة للطلب الحالي والطلب الذي يليه، ثم تُحذف تلقائياً.
     * 
     * @param string $key اسم الرسالة (مثال: 'success')
     * @param string $message نص الرسالة
     */
    public function setFlash($key, $message)
    {
        $_SESSION[self::FLASH_KEY][$key] = [
            'remove' => false,
            'value' => $message
        ];
    }

    /**
     * التحقق مما إذا كانت الجلسة (Session) قد بدأت بالفعل
     * 
     * @return bool
     */
    public function is_session_started()
    {
        if ( php_sapi_name() !== 'cli' ) {
            if ( version_compare(phpversion(), '5.4.0', '>=') ) {
                return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
            } else {
                return session_id() === '' ? FALSE : TRUE;
            }
        }
        return FALSE;
    }

    /**
     * جلب رسالة فلاش معينة
     * 
     * @param string $key اسم الرسالة
     * @return string|false القيمة أو false إذا لم توجد
     */
    public function getFlash($key)
    {
        return $_SESSION[self::FLASH_KEY][$key]['value'] ?? false;
    }

    /**
     * حفظ قيمة في الجلسة
     * 
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * جلب قيمة من الجلسة
     * 
     * @param string $key
     * @return mixed|false
     */
    public function get($key)
    {
        return $_SESSION[$key] ?? false;
    }

    /**
     * حذف قيمة معينة من الجلسة
     * 
     * @param string $key
     */
    public function remove($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * تدمير الجلسة بالكامل (تسجيل الخروج)
     * يقوم بثلاث خطوات أمنية متسلسلة:
     *  1. تفريغ محتوى $_SESSION من الذاكرة
     *  2. حذف Cookie الجلسة من متصفح المستخدم
     *  3. تدمير بيانات الجلسة من السيرفر
     */
    public function destroy(): void
    {
        // الخطوة 1: تفريغ مصفوفة الجلسة في الذاكرة
        $_SESSION = [];

        // الخطوة 2: [أمان] حذف Cookie الجلسة من متصفح المستخدم
        // بدون هذه الخطوة يظل المتصفح يحتفظ بالـ Cookie ويمكنه إعادة استخدامه
        if (ini_get('session.use_cookies') && isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // الخطوة 3: تدمير بيانات الجلسة من السيرفر
        session_destroy();
    }

    /**
     * الدالة المدمرة (Destructor)
     * تنفذ تلقائياً في نهاية الطلب وتقوم بمسح رسائل الفلاش القديمة.
     */
    public function __destruct()
    {
        $this->removeFlashMessages();
    }

    /**
     * حذف رسائل الفلاش التي تم وضع علامة 'remove' عليها مسبقاً
     */
    private function removeFlashMessages()
    {
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => $flashMessage) {
            if ($flashMessage['remove']) {
                unset($flashMessages[$key]);
            }
        }
        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }
}
