<?php
// autoload.php

// — 1. Tự include config chung để nạp hằng số DB_
require_once __DIR__ . '/config.php';

// — 2. Đăng ký PSR-4 autoloader cho namespace App\
spl_autoload_register(function (string $class) {
    $prefix   = 'App\\';
    $base_dir = __DIR__ . '/src/';

    // Nếu lớp không thuộc namespace App\, bỏ qua
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Tách phần còn lại của tên lớp
    $relative = substr($class, $len);

    // Chuyển namespace thành đường dẫn file
    $file = $base_dir . str_replace('\\', '/', $relative) . '.php';

    // Nếu tồn tại file, nạp vào
    if (file_exists($file)) {
        require $file;
    }
});
