<?php
namespace yurni\Http;

/**
 * كلاس الاستجابة (Response)
 * مسؤول عن إرسال المخرجات للمستخدم، سواء كانت HTML، JSON، أو حتى عمليات إعادة التوجيه (Redirect).
 */
class Response
{

    protected static $CONTENT_TYPE_HTML = "text/html; charset=UTF-8";
    protected static $CONTENT_TYPE_JSON = "application/json; charset=UTF-8";
    protected static $HEADER_CONTENT_TYPE = "Content-Type";

    protected array $header;
    protected $body;

    /**
     * منشئ الكلاس
     */
    public function __construct()
    {
        $this->body = null;
        $this->reset();
    }

    /**
     * تعيين كود حالة الـ HTTP (مثل 200, 404, 500)
     * 
     * @param int $code
     * @return self
     */
    public function setStatusCode(int $code)
    {
        http_response_code($code);
        return $this;
    }

    /**
     * الحصول على كود حالة الـ HTTP الحالي
     * 
     * @return int|null
     */
    public function getStatusCode()
    {
        return http_response_code() ?? null;
    }

    /**
     * تعيين هيدر (Header) محدد للاستجابة
     * 
     * @param string $type اسم الهيدر (مثال: Content-Type)
     * @param string $val القيمة
     * @return self
     */
    public function setHeader($type, $val)
    {
        header($type . ': ' . $val);
        return $this;
    }

    /**
     * تعيين نوع المحتوى (Content-Type)
     * 
     * @param string $val
     * @return self
     */
    public function setContentType($val)
    {
        return $this->setHeader(self::$HEADER_CONTENT_TYPE, $val);
    }

    /**
     * تحضير استجابة بصيغة JSON
     * 
     * @param array $data البيانات المراد تحويلها لـ JSON
     * @param int $status كود حالة الـ HTTP (الافتراضي 200)
     * @return self
     */
    public function json(array $data = [], int $status = 200)
    {
        $json = json_encode($data);
        $this->body = $json;
        $this->setContentType(self::$CONTENT_TYPE_JSON)
            ->setStatusCode($status);
        return $this;
    }

    /**
     * الحصول على المحتوى الحالي المخزن
     * 
     * @return string|null
     */
    public function body()
    {
        return $this->body;
    }

    /**
     * تعيين محتوى الاستجابة بشكل مباشر
     * 
     * @param string $body
     * @return self
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * تحضير استجابة بصيغة HTML (الوضع الافتراضي)
     * 
     * @param string $content كود الـ HTML
     * @param int $status كود حالة الـ HTTP (الافتراضي 200)
     * @return self
     */
    public function html($content = "", int $status = 200)
    {
        $this->setStatusCode($status)
            ->setContentType(self::$CONTENT_TYPE_HTML);
        $this->body = $content;
        return $this;
    }

    /**
     * إعادة توجيه المستخدم (Redirect) إلى مسار آخر
     * 
     * @param string $url الرابط المراد التوجه إليه
     * @return self
     */
    public function redirect($url)
    {
        return $this->setHeader("Location", $url);
    }

    /**
     * مسح المحتوى الحالي وتفريغ كائن الاستجابة
     * 
     * @return self
     */
    public function reset()
    {
        $this->body = null;
        return $this;
    }
}
