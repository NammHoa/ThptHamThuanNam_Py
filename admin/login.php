<?php
session_start();
require '../config.php';

if (!empty($_SESSION['is_admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Lỗi kết nối CSDL: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("SELECT id, password FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($admin_id, $hash);
            $stmt->fetch();
            if (password_verify($password, $hash)) {
            session_regenerate_id(true);  // chống session fixation
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_id'] = $admin_id;
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập Admin</title>
  <style>
    * { box-sizing: border-box; }

    body {
      font-family: "Segoe UI", Arial, sans-serif;
      margin: 0; padding: 0;
      min-height: 100vh;
      background: linear-gradient(135deg, #003366 0%, #004080 60%, #0066cc 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .login-wrapper {
      width: 100%;
      max-width: 420px;
      padding: 20px;
    }

    /* Logo + tên trường */
    .login-header {
      text-align: center;
      margin-bottom: 25px;
    }
    .login-header img {
      height: 80px; width: 80px;
      border-radius: 50%;
      border: 3px solid rgba(255,255,255,0.6);
      background: #fff;
      padding: 3px;
      object-fit: contain;
      margin-bottom: 12px;
    }
    .login-header h2 {
      color: #fff;
      margin: 0 0 4px 0;
      font-size: 18px;
      text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
    }
    .login-header p {
      color: rgba(255,255,255,0.7);
      margin: 0;
      font-size: 13px;
    }

    /* Card đăng nhập */
    .login-card {
      background: #fff;
      border-radius: 16px;
      padding: 35px 30px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .login-card h3 {
      text-align: center;
      color: #004080;
      margin: 0 0 25px 0;
      font-size: 20px;
    }

    label {
      display: block;
      font-weight: bold;
      color: #444;
      font-size: 14px;
      margin-bottom: 6px;
    }

    .input-group {
      position: relative;
      margin-bottom: 18px;
    }
    .input-group input {
      width: 100%;
      padding: 12px 14px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-size: 15px;
      transition: border 0.2s, box-shadow 0.2s;
      outline: none;
    }
    .input-group input:focus {
      border-color: #004080;
      box-shadow: 0 0 0 3px rgba(0,64,128,0.1);
    }

    /* Nút đăng nhập */
    .btn-login {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, #003366, #0066cc);
      color: #fff;
      font-size: 16px;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      letter-spacing: 0.5px;
      box-shadow: 0 4px 12px rgba(0,64,128,0.3);
      transition: background 0.2s, transform 0.1s;
      margin-top: 5px;
    }
    .btn-login:hover {
      background: linear-gradient(135deg, #002244, #0055aa);
      transform: translateY(-1px);
    }
    .btn-login:active { transform: translateY(0); }

    /* Thông báo lỗi */
    .error-box {
      background: #f8d7da;
      color: #721c24;
      border-left: 4px solid #dc3545;
      padding: 10px 14px;
      border-radius: 6px;
      font-size: 14px;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Link quay về */
    .back-link {
      text-align: center;
      margin-top: 20px;
    }
    .back-link a {
      color: rgba(255,255,255,0.8);
      text-decoration: none;
      font-size: 14px;
      transition: color 0.2s;
    }
    .back-link a:hover { color: #fff; }

    @media (max-width: 480px) {
      .login-card { padding: 25px 20px; }
      .login-header img { height: 65px; width: 65px; }
      .login-header h2 { font-size: 16px; }
    }
  </style>
</head>
<body>

<div class="login-wrapper">

  <div class="login-header">
    <img src="../images/logo-thpt-cent.jpg" alt="Logo">
    <h2>TRƯỜNG THPT HÀM THUẬN NAM</h2>
    <p>Hệ thống đăng ký nguyện vọng lớp 10</p>
  </div>

  <div class="login-card">
    <h3>Đăng nhập Admin</h3>

    <?php if ($error): ?>
      <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <label for="username">Tên đăng nhập</label>
      <div class="input-group">
        <input type="text" id="username" name="username"
               placeholder="Nhập tên đăng nhập"
               value="<?= htmlspecialchars($username ?? '') ?>"
               required autofocus>
      </div>

      <label for="password">Mật khẩu</label>
      <div class="input-group">
        <input type="password" id="password" name="password"
               placeholder="Nhập mật khẩu"
               required>
      </div>

      <button type="submit" class="btn-login">Đăng nhập</button>
    </form>
  </div>

  <div class="back-link">
    <a href="../index.php">← Quay về trang đăng ký</a>
  </div>

</div>

</body>
</html>