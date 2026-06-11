<?php
require_once __DIR__ . '/../config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

// Xử lý form khi submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["deadline"])) {
    $deadline = $_POST["deadline"];

    // Kiểm tra xem đã có bản ghi chưa
    $check = $conn->prepare("SELECT * FROM thietlap WHERE ten = 'han_dang_ky' LIMIT 1");
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Cập nhật
        $stmt = $conn->prepare("UPDATE thietlap SET gia_tri = ? WHERE ten = 'han_dang_ky'");
        $stmt->bind_param("s", $deadline);
    } else {
        // Chưa có, chèn mới
        $stmt = $conn->prepare("INSERT INTO thietlap (ten, gia_tri) VALUES ('han_dang_ky', ?)");
        $stmt->bind_param("s", $deadline);
    }

    $stmt->execute();
    $message = "✅ Đã cập nhật hạn đăng ký thành công.";
}

// Lấy giá trị hiện tại nếu có
$deadline = "";
$result = $conn->query("SELECT gia_tri FROM thietlap WHERE ten='han_dang_ky' LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $deadline = $row["gia_tri"];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thiết lập hạn đăng ký</title>
	<style>
    /* ---- Reset & Layout ---- */
    body { font-family: Arial, sans-serif; background: #f0f0f0; margin: 0; padding: 0; }
    header { background: #004080; color: #fff; padding: 20px; text-align: center; }
    header a { color: #fff; margin: 0 10px; text-decoration: none; }
	main { text-align:center; margin: 10px}
  </style>
  <link rel="stylesheet" href="admin_style.css">

</head>
<body>
	<header>
    <h1>⏰Thiết lập thời gian hết hạn đăng ký nguyện vọng</h1>
   	</header>
	<main>
    <?php if (isset($message)) echo "<p style='color: green;'>$message</p>"; ?>

    <form method="post">
        <label for="deadline">Thời hạn (yyyy-mm-dd hh:mm:ss):</label><br>
        <input type="datetime-local" name="deadline" required
            value="<?= date('Y-m-d\TH:i', strtotime($deadline)) ?>"><br><br>
        <button type="submit">💾 Lưu hạn đăng ký</button>
    </form>
    <br><a href="dashboard.php">← QUAY VỀ TRANG QUẢN TRỊ</a>
	</main>
</body>
</html>
