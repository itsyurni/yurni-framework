<?php
namespace yurni\Exception;

/**
 * استثناء للموارد غير الموجودة (Not Found 404)
 */
class NotFoundException extends \Exception {
    protected $code = 404;
    
    /**
     * إرجاع رسالة الخطأ منسقة
     * @return string
     */
    public function errorMessage() {
        return 'Error : '.$this->getMessage();
    }
}
