<?php
session_start();
require '../config.php';    // Kết nối DB qua hằng số DB_*
<link rel="stylesheet" href="admin_style.css">

// Nếu đã login, chuyển thẳng tới dashboard
if (!empty($_SESSION['is_admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Lấy dữ liệu từ form
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // 2. Kiểm tra không để trống
    if (!$username || !$password) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        // 3. Kết nối CSDL
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Lỗi kết nối CSDL: " . $conn->connect_error);
        }


        // 4. Truy vấn user theo username
        $stmt = $conn->prepare("SELECT id, password FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
			
            $stmt->bind_result($admin_id, $hash);
            $stmt->fetch();
            // 5. So khớp mật khẩu
            if ($password === $hash) {
                // 6. Đăng nhập thành công
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_id'] = $admin_id;
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Mật khẩu không đúng.';
            }
        } else {
            $error = 'Tên đăng nhập không tồn tại.';
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Admin Login – <?php echo ADMIN_USER; ?></title>
  <style>
    body { font-family: Arial; background: #f0f0f0; padding: 50px; }
    .box { background: #fff; padding: 30px; max-width: 400px; margin: auto; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { margin-top: 0; }
    input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; }
    button { width: 100%; padding: 10px; background: #28a745; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    button:hover { background: #218838; }
    .error { color: red; font-size: 14px; margin-top: 10px; }
  </style>
  <link rel="stylesheet" href="admin_style.css">
</head>
<body>
  <div class="box">
    <h2>Đăng nhập Admin</h2>
    <form method="POST">
      <input name="username" placeholder="Tên đăng nhập" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
      <input type="password" name="password" placeholder="Mật khẩu" required>
      <button type="submit">Đăng nhập</button>
    </form>
    <?php if ($error): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
  </div>
</body>
</html>
