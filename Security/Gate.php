<?php
namespace yurni\Security;

use yurni\Http\Session;

/**
 * Authorization Gate
 *
 * يمكن استخدام هذا الكلاس لفحص الصلاحيات عبر الجلسة.
 * هذا تنفيذ بسيط ويمكن توسيعه لاحقًا ليتكامل مع نظام المستخدمين الكامل.
 */
class Gate
{
    /**
     * فحص صلاحية معينة للمستخدم الحالي.
     *
     * @param string $permission
     * @return bool
     */
    public static function check(string $permission): bool
    {
        $session = new Session();
        $userPermissions = $session->get('permissions', []);

        if (!is_array($userPermissions)) {
            return false;
        }

        return in_array($permission, $userPermissions, true);
    }
}
