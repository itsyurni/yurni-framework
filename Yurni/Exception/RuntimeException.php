<?php
namespace yurni\Exception;

/**
 * استثناء لأخطاء وقت التشغيل (Runtime Error)
 */
class RuntimeException extends \Exception {
    protected $code = 500; // Changed from 403 to 500 for better semantics
    
    /**
     * إرجاع رسالة الخطأ منسقة
     * @return string
     */
    public function errorMessage() {
        return 'Runtime Error : <b>'.$this->getMessage().'</b>';
    }
}
