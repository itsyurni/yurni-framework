# العرض ومحرك القوالب

Yurni يقدم طبقة عرض خفيفة تدعم قوالب بامتداد `.php` مع علامات خاصة.

## عرض قالب

```php
return View::render('home', ['title' => 'الرئيسية']);
```

في المتحكم يمكنك استخدام:

```php
return $this->render('home', ['title' => 'الرئيسية']);
```

## قواعد القالب

### طباعة قيمة آمنة

```twig
{{ username }}
```

هذا يعادل:

```php
<?php echo htmlspecialchars((string)$username, ENT_QUOTES, 'UTF-8'); ?>
```

### طباعة raw بدون escaping

```twig
{{{ htmlContent }}}
```

### الشروط

```twig
{% if $user['active'] %}
    مرحبًا {{ $user['name'] }}
{% else %}
    حسابك غير مفعل
{% endif %}
```

### الحلقات

```twig
{% foreach $posts as $post %}
    <li>{{ $post['title'] }}</li>
{% endforeach %}
```

### الكتل والوراثة

```twig
{% block content %}
    <h1>محتوى الصفحة</h1>
{% endblock %}

{% yield content %}
```

### تضمين قالب أو Extends

```twig
{% include 'partials/header' %}
```

### حماية نصية

```twig
{% verbatim %}
    {{ this_will_not_be_escaped }}
{% endverbatim %}
```

### تنفيذ PHP آمن

افتراضيًا، `view_allow_php` معطل. لتفعيل PHP الصريح في القوالب:

```env
view_allow_php=true
```

ثم استخدم:

```twig
{% php echo 'Hello'; %}
```

## إعدادات العرض

القيم المدعومة في `.env` أو `Config`:

- `views_path` — مسار قوالب العرض.
- `views_cache_path` — مجلد تخزين القوالب المجمعة.
- `view_cache` — تفعيل الكاش.
- `view_optimize` — ضغط الكود المجمّع.
- `view_allow_php` — تمكين تنفيذ تعابير PHP الداخلية.

## تخزين متغيرات عامة

يمكنك تمرير بيانات افتراضية لجميع القوالب عبر:

```php
$app->setViewAttr(['appName' => 'Yurni']);
```

ثم استخدامها في أي قالب:

```twig
{{ appName }}
```
