<?php
// Bắt đầu hoặc tiếp tục session
session_start();

// 1. Xoá tất cả dữ liệu session
$_SESSION = [];

// 2. Nếu muốn xoá hoàn toàn session cookie trên client
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),    // Tên cookie session
        '',                // Giá trị rỗng
        time() - 42000,    // Thời gian đã qua => trình duyệt xoá
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Huỷ session trên server
session_destroy();

// 4. Regenerate ID để tránh session fixation
session_start();
session_regenerate_id(true);
session_destroy();

// 5. Redirect về trang main
header('Location: ../index.php');
exit;
?>
