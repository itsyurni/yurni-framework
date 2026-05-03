<?php
namespace yurni\Http;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

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
    protected ?string $body;

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
    public function setStatusCode(int $code): self
    {
        http_response_code($code);
        return $this;
    }

    /**
     * الحصول على كود حالة الـ HTTP الحالي
     * 
     * @return int|null
     */
    public function getStatusCode(): ?int
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
    public function setHeader(string $type, string $val): self
    {
        if (headers_sent($file, $line)) {
            throw new RuntimeException("Cannot set header after output has started ({$file}:{$line}).");
        }

        $headerName = $this->sanitizeHeaderName($type);
        $headerValue = $this->sanitizeHeaderValue($val);

        header($headerName . ': ' . $headerValue);
        return $this;
    }

    /**
     * تعيين نوع المحتوى (Content-Type)
     * 
     * @param string $val
     * @return self
     */
    public function setContentType(string $val): self
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
    public function json(array $data = [], int $status = 200): self
    {
        try {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $responseStatus = $status;
        } catch (JsonException) {
            $json = '{"error":"Failed to encode JSON response."}';
            $responseStatus = 500;
        }

        $this->body = $json;
        $this->setContentType(self::$CONTENT_TYPE_JSON)
            ->setStatusCode($responseStatus);
        return $this;
    }

    /**
     * الحصول على المحتوى الحالي المخزن
     * 
     * @return string|null
     */
    public function body(): ?string
    {
        return $this->body;
    }

    /**
     * تعيين محتوى الاستجابة بشكل مباشر
     * 
     * @param string $body
     * @return self
     */
    public function setBody(string $body): self
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
    public function html(string $content = "", int $status = 200): self
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
    public function redirect(string $url, int $status = 302, bool $allowExternal = false): self
    {
        $location = self::sanitizeRedirectUrl($url, '/', $allowExternal);

        $this->setStatusCode($status);
        return $this->setHeader("Location", $location);
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

    public static function sanitizeRedirectUrl(string $url, string $fallback = '/', bool $allowExternal = false): string
    {
        $cleanUrl = trim((string) preg_replace('/[\r\n]+/', '', $url));
        $cleanUrl = str_replace('\\', '/', $cleanUrl);
        if ($cleanUrl === '') {
            return $fallback;
        }

        if ($allowExternal) {
            return $cleanUrl;
        }

        if (str_starts_with($cleanUrl, '//')) {
            return $fallback;
        }

        $scheme = parse_url($cleanUrl, PHP_URL_SCHEME);
        $host = parse_url($cleanUrl, PHP_URL_HOST);

        if ($host !== null || $scheme !== null) {
            $currentHostHeader = $_SERVER['HTTP_HOST'] ?? null;
            $currentHost = $currentHostHeader !== null
                ? parse_url('http://' . $currentHostHeader, PHP_URL_HOST)
                : null;

            if ($host !== null && $currentHost !== null && strcasecmp($host, $currentHost) === 0) {
                $path = parse_url($cleanUrl, PHP_URL_PATH) ?: '/';
                $query = parse_url($cleanUrl, PHP_URL_QUERY);
                $fragment = parse_url($cleanUrl, PHP_URL_FRAGMENT);

                if ($query !== null && $query !== '') {
                    $path .= '?' . $query;
                }

                if ($fragment !== null && $fragment !== '') {
                    $path .= '#' . $fragment;
                }

                return $path;
            }

            return $fallback;
        }

        return $cleanUrl;
    }

    private function sanitizeHeaderName(string $name): string
    {
        if ($name === '' || preg_match('/^[A-Za-z0-9-]+$/', $name) !== 1) {
            throw new InvalidArgumentException("Invalid header name [{$name}].");
        }

        return $name;
    }

    private function sanitizeHeaderValue(string $value): string
    {
        return trim((string) preg_replace('/[\r\n]+/', '', $value));
    }
}
