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

## العربية

**Yurni** هو إطار عمل PHP عصري وخفيف الوزن، مصمم من أجل البساطة والأداء العالي. يوفر الأدوات الأساسية لبناء تطبيقات ويب قوية دون التعقيدات الزائدة للأطر الضخمة.

### ✨ المميزات
- **نظام توجيه مرن (Routing)**: تعريف مسارات نظيفة ومعبرة بسهولة.
- **بنية MVC**: فصل كامل للمنطق (Logic) عن العرض (Presentation).
- **حاوية حقن التبعيات (DI Container)**: إدارة دورة حياة الكائنات بكفاءة.
- **منشئ الاستعلامات (Query Builder)**: تعامل سلس وآمن مع قواعد البيانات.
- **دعم البرمجيات الوسيطة (Middlewares)**: معالجة الطلبات قبل وصولها للمتحكم.
- **حماية مدمجة**: حماية تلقائية ضد هجمات CSRF والثغرات الشائعة.
- **محرك قوالب**: نظام عرض ديناميكي وبسيط.

### 🚀 ابدأ الآن
قم بتعريف المسارات في ملف `public/index.php` وابدأ بناء تطبيقك فوراً!

### ⚙️ التثبيت والتنصيب
1. **تحميل المشروع**:
   ```bash
   git clone https://github.com/itsyurni/yurni-framework.git
   ```
2. **تثبيت المكتبات**:
   ```bash
   composer install
   ```
3. **إعداد البيئة**:
   قم بنسخ ملف الإعدادات وقم بضبط بيانات قاعدة البيانات:
   ```bash
   cp .env.example .env
   ```
4. **تشغيل التطبيق**:
   استخدم سيرفر PHP الداخلي للبدء:
   ```bash
   php -S localhost:8000 -t public
   ```

## 📄 License
The Yurni Framework is open-sourced software licensed under the [MIT license](LICENSE).