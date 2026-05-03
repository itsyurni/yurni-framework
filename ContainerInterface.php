<?php
declare(strict_types=1);

namespace yurni;

/**
 * واجهة حاوية التبعيات المتوافقة مع معيار PSR-11
 * PSR-11 Container Interface
 */
interface ContainerInterface
{
    /**
     * البحث عن كائن داخل الحاوية عن طريق المُعرّف الخاص به وإرجاعه.
     *
     * @param string $id المُعرّف (عادة يكون اسم الـ Class)
     * @return mixed الكائن المطلوب
     * @throws \Exception إذا لم يتم العثور على الكائن
     */
    public function get(string $id): mixed;

    /**
     * التحقق مما إذا كانت الحاوية تمتلك كائناً أو تعريفاً للمُعرّف المطلوب.
     * ترجع true إذا كان موجوداً، و false في حال العكس.
     *
     * @param string $id المُعرّف
     * @return bool
     */
    public function has(string $id): bool;
}
