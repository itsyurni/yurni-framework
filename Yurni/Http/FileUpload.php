<?php
namespace yurni\Http;

use yurni\Exception\RuntimeException;

/**
 * كلاس التعامل مع الملفات المرفوعة (File Upload)
 * يوفر واجهة سهلة للوصول لمعلومات الملف المرفوع ونقله إلى المجلد النهائي.
 */
class FileUpload {

    /**
     * @var string اسم الملف (بدون الامتداد)
     */
    public $name;

    /**
     * @var string امتداد الملف (مثل jpg, png)
     */
    public $extension;

    /**
     * @var string مسار الملف المؤقت في السيرفر
     */
    protected $tmp;

    /**
     * @var int حجم الملف بالبايت
     */
    protected $size;

    /**
     * @var int رمز الخطأ (0 يعني لا يوجد خطأ)
     */
    protected $error;

    /**
     * @var string نوع الملف الحقيقي (MIME Type) محدد من السيرفر وليس من بيانات المستخدم
     */
    protected $mimeType;

    /**
     * @var string المسار الحالي للملف (يتغير بعد النقل)
     */
    protected $location;

    /**
     * @var string الاسم الأصلي للملف كما رفعه المستخدم
     */
    protected $originalName;

    /**
     * منشئ الكلاس
     * 
     * @param array $_file مصفوفة الملف المفردة من $_FILES
     */
    public function __construct(array $_file)
    {
        $this->tmp          = $_file['tmp_name'];
        $this->size         = $_file['size'];
        $this->error        = $_file['error'];
        $this->location     = $this->tmp;
        $this->originalName = $_file['name'];
        $this->name         = pathinfo($_file['name'], PATHINFO_FILENAME);
        $this->extension    = pathinfo($_file['name'], PATHINFO_EXTENSION);

        // [أمان] تحديد MIME Type من جهة السيرفر فقط باستخدام finfo
        // وليس من قيمة $_FILES['type'] التي يتحكم بها المستخدم (ثغرة أمان)
        if (is_uploaded_file($this->tmp) && function_exists('finfo_open')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $this->mimeType = $finfo->file($this->tmp);
        } else {
            // احتياطي: الرجوع لبيانات الطلب إذا كانت finfo غير متاحة
            $this->mimeType = $_file['type'] ?? 'application/octet-stream';
        }
    }

    /**
     * جلب الاسم الأصلي للملف
     * 
     * @return string
     */
    public function getClientOriginalName()
    {
        return $this->originalName;
    }

    /**
     * جلب الاسم الكامل للملف مع الامتداد
     * 
     * @return string
     */
    public function getFilename()
    {
        $ext = empty($this->extension) ? "" : "." . $this->extension;
        return $this->name . $ext;
    }

    /**
     * جلب المسار المؤقت للملف
     * 
     * @return string
     */
    public function getTemporaryFile()
    {
        return $this->tmp;
    }

    /**
     * جلب نوع الملف (Mime Type)
     * 
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * جلب المسار الفعلي الحالي للملف
     * 
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * نقل الملف المرفوع من المسار المؤقت إلى المسار النهائي
     * 
     * @param string $location المسار المراد النقل إليه (مجلد)
     * @param string|null $filename اسم الملف الجديد (اختياري)
     * @throws RuntimeException في حال فشل النقل أو عدم وجود صلاحيات
     */
    public function move($location, $filename = null)
    {
        if ($filename) {
            $pathinfo = pathinfo($filename);
            $this->extension = $pathinfo['extension'] ?? $this->extension;
            $this->name = $pathinfo['filename'];
        }

        if(!is_uploaded_file($this->tmp)) return FALSE;

        $location = rtrim($location, "/");

        if(!is_dir($location)) {
            throw new RuntimeException("Upload directory '{$location}' not found", 1);
        } else if(!is_writable($location)) {
            throw new RuntimeException("Upload directory '{$location}' is not writable", 2);
        }
        
        $filepath = $location . "/" . $this->getFilename();

        move_uploaded_file($this->tmp, $filepath);   
        
        $has_moved = (false == is_uploaded_file($this->tmp));

        if($has_moved) {
            $this->location = $filepath;
        } else {
            throw new \RuntimeException("Upload file failed because unexpected reason. Maybe there is miss configuration in your php.ini settings", 3);
        }
    }

    /**
     * جلب محتويات الملف كنص عند طباعة الكائن
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) file_get_contents($this->getLocation());
    }
}
