# Yurni Framework 🚀

[English](#english) | [العربية](#العربية)

---

## English

**Yurni** is a lightweight, modern PHP framework designed for simplicity and performance. It provides the essential tools to build robust web applications without the overhead of massive frameworks.

### ✨ Features
- **Intuitive Routing**: Define clean and expressive routes easily.
- **MVC Architecture**: Clean separation of concerns with Models, Views, and Controllers.
- **Dependency Injection**: Powerful DI Container for managing object lifecycles.
- **Query Builder**: Fluent and secure database interaction layer.
- **Middleware Support**: Intercept and process requests with ease.
- **Security First**: Built-in protection against CSRF and common vulnerabilities.
- **Template Engine**: Dynamic view rendering with clean syntax.

### 🚀 Quick Start
```php
use yurni\Application;
use yurni\Http\Response;

$app = new Application();

$app->get('/', function(Response $response) {
    return $response->html("<h1>Welcome to Yurni!</h1>");
});

$app->run();
```

### ⚙️ Installation
1. **Clone the repository**:
   ```bash
   git clone https://github.com/your-username/yurni-framework.git
   ```
2. **Install dependencies**:
   ```bash
   composer install
   ```
3. **Setup environment**:
   Copy the example environment file and configure your database settings:
   ```bash
   cp .env.example .env
   ```
4. **Run the application**:
   Use the PHP built-in server to start:
   ```bash
   php -S localhost:8000 -t public
   ```

---

---

## العربية 🇸🇦

**Yurni** هو إطار عمل PHP عصري وخفيف الوزن، مصمم من أجل البساطة والأداء العالي. يوفر الأدوات الأساسية لبناء تطبيقات ويب قوية دون التعقيدات الزائدة للأطر الضخمة.

### ✨ المميزات
- **نظام توجيه مرن (Routing)**: تعريف مسارات نظيفة ومعبرة بسهولة.
- **بنية MVC**: فصل كامل للمنطق (Logic) عن العرض (Presentation).
- **حاوية حقن التبعيات (DI Container)**: إدارة دورة حياة الكائنات بكفاءة.
- **منشئ الاستعلامات (Query Builder)**: تعامل سلس وآمن مع قواعد البيانات.
- **دعم البرمجيات الوسيطة (Middlewares)**: معالجة الطلبات قبل وصولها للمتحكم.
- **حماية مدمجة**: حماية تلقائية ضد هجمات CSRF والثغرات الشائعة.
- **محرك قوالب**: نظام عرض ديناميكي وبسيط.

### 🛠️ متطلبات التشغيل
قبل البدء، تأكد من توفر المتطلبات التالية في بيئتك:
- **PHP**: إصدار 8.0 أو أعلى.
- **Composer**: لإدارة المكتبات والاعتمادات.
- **خادم ويب**: مثل Apache أو Nginx، أو استخدام خادم PHP المدمج للتطوير.
- **قاعدة بيانات**: (اختياري) MySQL أو SQLite.

### ⚙️ إعداد بيئة العمل (Installation)

بما أن إطار العمل متاح كحزمة Composer، يمكنك تثبيته في مشروعك الجديد عبر الخطوات التالية:

1. **إنشاء مشروع جديد**:
   ```bash
   mkdir my-new-app
   cd my-new-app
   ```

2. **تثبيت إطار العمل**:
   ```bash
   composer require yurni/framework
   ```

3. **إعداد بنية المجلدات**:
   يجب أن يحتوي مشروعك على المجلدات التالية (بشكل افتراضي):
   - `app/Controllers`: للمتحكمات.
   - `app/Models`: للنماذج.
   - `app/views`: لملفات العرض والقوالب.
   - `public`: للملفات العامة ونقطة الدخول `index.php`.

4. **إعداد ملف البيئة (.env)**:
   قم بإنشاء ملف باسم `.env` في المجلد الرئيسي وضبط الإعدادات:
   ```env
   APP_NAME=YurniApp
   APP_DEBUG=true
   
   DB_DRIVER=mysql
   DB_HOST=127.0.0.1
   DB_NAME=your_database
   DB_USER=root
   DB_PASS=
   ```

5. **نقطة الدخول (public/index.php)**:
   قم بإنشاء الملف وابدأ بتعريف مساراتك:
   ```php
   <?php
   require_once __DIR__ . '/../vendor/autoload.php';
   
   use yurni\Application;
   
   $app = new Application(realpath(__DIR__ . '/../'));
   
   $app->get('/', function() {
       return "Welcome to Yurni Framework!";
   });
   
   $app->run();
   ```

6. **تشغيل المشروع**:
   استخدم الأمر التالي للتشغيل السريع:
   ```bash
   php -S localhost:8000 -t public
   ```

### 📄 الترخيص
إطار عمل Yurni هو برمجية مفتوحة المصدر مرخصة تحت رخصة [MIT](LICENSE).