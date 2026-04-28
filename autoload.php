<?php
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefixes = [
        'yurni\\' => __DIR__ . '/Yurni/',
        'App\\' => __DIR__ . '/app/',
    ];

    foreach ($prefixes as $prefix => $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
});

// تحميل الدوال المساعدة العامة (Global Helpers)
require_once __DIR__ . '/Yurni/Helpers/helpers.php';

