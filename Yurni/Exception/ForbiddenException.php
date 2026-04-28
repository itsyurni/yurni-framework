<?php
namespace yurni\Exception;

/**
 * استثناء للموارد الممنوعة (Forbidden 403)
 */
class ForbiddenException extends \Exception {
    protected $code = 403;
    
    /**
     * إرجاع رسالة الخطأ منسقة
     * @return string
     */
    public function errorMessage() {
        return '<b>'.$this->getMessage().'</b>';
    }
}
