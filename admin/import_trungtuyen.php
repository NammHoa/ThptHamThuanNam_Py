<?php
session_start();

// 1) Nạp Composer autoload (đã cài qua composer install) và config chung
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// 2) Bảo vệ trang: chỉ Admin mới được phép truy cập
if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

// 3) Kết nối CSDL
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Lỗi kết nối CSDL: " . $conn->connect_error);
}

$resultMsg = '';

// 4) Nếu form đã POST file Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    // 4.1) Đọc file Excel bằng PhpSpreadsheet
    $spreadsheet = IOFactory::load($_FILES['file_excel']['tmp_name']);
    $sheetData   = $spreadsheet->getActiveSheet()->toArray();

    $count  = 0;
    $errors = [];

    // 4.2) Duyệt từng dòng, bắt đầu từ index 1 (bỏ header)
    for ($i = 1; $i < count($sheetData); $i++) {
        $rowNum = $i + 1;
        $sbd    = trim($sheetData[$i][1] ?? '');
        $name   = trim($sheetData[$i][2] ?? '');

        if ($sbd === '' || $name === '') {
            $errors[] = "Dòng $rowNum: Thiếu SBD hoặc Họ tên.";
            continue;
        }

        // 4.3) Chèn vào bảng danh_sach_trung_tuyen
        $stmt = $conn->prepare("
            INSERT IGNORE INTO danh_sach_trung_tuyen (so_bao_danh, ho_ten)
            VALUES (?, ?)
        ");
        $stmt->bind_param("ss", $sbd, $name);
        if ($stmt->execute()) {
            $count++;
        } else {
            $errors[] = "Dòng $rowNum: Lỗi CSDL - " . $stmt->error;
        }
        $stmt->close();
    }

    // 4.4) Tạo message kết quả
    $resultMsg = "✅ Đã import thành công $count dòng.";
    if (!empty($errors)) {
        $resultMsg .= "\n" . implode("\n", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Import danh sách trúng tuyển</title>
  <style>
    body { font-family: Arial, background: #f0f0f0; margin: 0; padding: 0; }
    header { background: #004080; color: #fff; padding: 20px; text-align: center; }
    header a { color: #fff; margin: 0 10px; text-decoration: none; }
    main { padding: 20px; }
    .container { background: #fff; padding: 30px; max-width: 600px; margin: 40px auto; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    input[type="file"] { display: block; width: 100%; margin-top: 15px; }
    button { margin-top: 15px; padding: 10px 20px; background: #28a745; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    button:hover { background: #218838; }
    .result { white-space: pre-wrap; font-family: monospace; background: #e9f7ef; padding: 15px; border-radius: 4px; margin-top: 20px; }
  </style>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="admin_style.css">
</head>
<body>

  <header>
    <h1>Quản lý danh sách trúng tuyển</h1>
    <a href="dashboard.php">Dashboard</a>
    <a href="logout.php">Logout</a>
  </header>

  <main>
    <div class="container">
      <h2>📥 Upload file Excel (.xlsx)</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="file" name="file_excel" accept=".xlsx" required>
        <button type="submit">Import</button>
      </form>

      <?php if ($resultMsg): ?>
        <div class="result"><?php echo nl2br(htmlspecialchars($resultMsg)); ?></div>
      <?php endif; ?>
    </div>
  </main>

</body>
</html>
