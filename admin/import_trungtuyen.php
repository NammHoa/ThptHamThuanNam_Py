<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Lỗi kết nối CSDL: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$resultMsg = '';
$resultType = '';
$count = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    $spreadsheet = IOFactory::load($_FILES['file_excel']['tmp_name']);
    $sheetData   = $spreadsheet->getActiveSheet()->toArray();

    for ($i = 3; $i < count($sheetData); $i++) {
        $rowNum = $i + 1;
        $sbd    = trim($sheetData[$i][1] ?? '');
        $name   = trim($sheetData[$i][2] ?? '');
        $ngay_sinh = trim($sheetData[$i][3] ?? '');

        if ($sbd === '' || $name === '') {
          continue;
          }

        $stmt = $conn->prepare("INSERT IGNORE INTO danh_sach_trung_tuyen (so_bao_danh, ho_ten, ngay_sinh) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $sbd, $name, $ngay_sinh);
        if ($stmt->execute()) {
            $count++;
        } else {
            $errors[] = "Dòng $rowNum: Lỗi CSDL - " . $stmt->error;
        }
        $stmt->close();
    }

    $resultType = empty($errors) ? 'success' : 'warning';
    $resultMsg  = "✅ Đã import thành công <strong>$count</strong> dòng.";
    if (!empty($errors)) {
        $resultMsg .= "<br><br>⚠️ Có " . count($errors) . " lỗi:<br>" . implode("<br>", $errors);
    }
}

if (isset($_GET['clear'])) {
    $conn->query("DELETE FROM danh_sach_trung_tuyen");
    $resultMsg  = "🗑️ Đã xoá toàn bộ danh sách trúng tuyển.";
    $resultType = 'danger';
}

$list = $conn->query("SELECT * FROM danh_sach_trung_tuyen ORDER BY id ASC");
$total = $list ? $list->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý danh sách trúng tuyển</title>
  <link rel="stylesheet" href="admin_style.css">
  <style>
    .stats-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #fff;
      border-radius: 10px;
      padding: 15px 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      flex-wrap: wrap;
      gap: 10px;
    }
    .stats-bar .stat {
      font-size: 15px;
      color: #444;
    }
    .stats-bar .stat strong {
      font-size: 22px;
      color: #004080;
      margin-right: 5px;
    }
    .btn-danger-outline {
      padding: 8px 16px;
      background: transparent;
      color: #dc3545;
      border: 1.5px solid #dc3545;
      border-radius: 6px;
      font-size: 13px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s;
      white-space: nowrap;
    }
    .btn-danger-outline:hover {
      background: #dc3545;
      color: #fff;
      text-decoration: none;
    }

    .upload-section {
      background: #fff;
      border-radius: 10px;
      padding: 25px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .upload-section h3 {
      color: #004080;
      margin: 0 0 5px 0;
      font-size: 17px;
    }
    .upload-section p {
      color: #888;
      font-size: 13px;
      margin: 0 0 15px 0;
    }
    .upload-area {
      border: 2px dashed #b0c4de;
      border-radius: 8px;
      padding: 20px;
      text-align: center;
      background: #f8faff;
      margin-bottom: 15px;
      transition: border-color 0.2s;
    }
    .upload-area:hover { border-color: #004080; }
    .upload-area input[type="file"] {
      display: block;
      margin: 0 auto;
      font-size: 14px;
    }
    .upload-area p {
      margin: 8px 0 0 0;
      font-size: 12px;
      color: #aaa;
    }
    .btn-upload {
      padding: 10px 28px;
      background: linear-gradient(135deg, #28a745, #20c050);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      font-weight: bold;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(40,167,69,0.3);
      transition: background 0.2s;
    }
    .btn-upload:hover { background: linear-gradient(135deg, #218838, #1aab42); }

    .result-box {
      padding: 14px 18px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
      line-height: 1.7;
    }
    .result-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
    .result-warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
    .result-danger  { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }

    /* Bảng danh sách */
    .list-table-wrapper {
      background: #fff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .list-table-wrapper table { min-width: 400px; }

    /* Card mobile */
    .list-card-list { display: none; }
    .list-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
      padding: 12px 15px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .list-card-stt {
      background: #004080; color: #fff;
      border-radius: 50%;
      width: 32px; height: 32px;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; font-weight: bold; flex-shrink: 0;
    }
    .list-card-info { flex: 1; }
    .list-card-name { font-weight: bold; color: #004080; font-size: 15px; }
    .list-card-sbd { font-size: 13px; color: #888; margin-top: 2px; }

    @media (max-width: 600px) {
      .list-table-wrapper { display: none; }
      .list-card-list { display: block; }
      .stats-bar { flex-direction: column; align-items: flex-start; }
      .upload-section { padding: 18px 15px; }
    }
  </style>
</head>
<body>

<header>
  <h1>📋 Quản lý danh sách trúng tuyển</h1>
  <div class="btn-group">
    <a href="dashboard.php" class="button">🏠 Dashboard</a>
    <a href="logout.php" class="button danger">🚪 Logout</a>
  </div>
</header>

<main>

  <?php if ($resultMsg): ?>
    <div class="result-box result-<?= $resultType ?>"><?= $resultMsg ?></div>
  <?php endif; ?>

  <!-- Thống kê + nút xoá -->
  <div class="stats-bar">
    <div class="stat">
      <strong><?= $total ?></strong> học sinh trong danh sách trúng tuyển
    </div>
    <?php if ($total > 0): ?>
      <a href="?clear=1"
         class="btn-danger-outline"
         onclick="return confirm('Xác nhận xoá TOÀN BỘ danh sách trúng tuyển?')">
        🗑️ Xoá toàn bộ
      </a>
    <?php endif; ?>
  </div>

  <!-- Form upload -->
  <div class="upload-section">
    <h3>📥 Upload file Excel (.xlsx)</h3>
    <p>File Excel cần có: Cột B = Số báo danh, Cột C = Họ và tên (hàng 1 là tiêu đề, dữ liệu từ hàng 2)</p>
    <form method="POST" enctype="multipart/form-data">
      <div class="upload-area">
        <input type="file" name="file_excel" accept=".xlsx" required>
        <p>Chỉ chấp nhận file .xlsx</p>
      </div>
      <button type="submit" class="btn-upload">📤 Import dữ liệu</button>
    </form>
  </div>

  <!-- Danh sách hiện tại -->
  <?php if ($total > 0): ?>
    <h2>Danh sách hiện tại</h2>

    <!-- Bảng desktop -->
    <div class="list-table-wrapper">
      <table>
        <thead>
          <tr>
            <th style="width:60px; text-align:center;">STT</th>
            <th>Họ và tên</th>
            <th>Số báo danh</th>
            <th>Ngày sinh</th>
          </tr>
        </thead>
        <tbody>
          <?php $stt = 1; $list->data_seek(0); while ($row = $list->fetch_assoc()): ?>
            <tr>
              <td style="text-align:center;"><?= $stt++ ?></td>
              <td><?= htmlspecialchars($row['ho_ten']) ?></td>
              <td><?= htmlspecialchars($row['so_bao_danh']) ?></td>
              <td><?= htmlspecialchars($row['ngay_sinh']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Card mobile -->
    <div class="list-card-list">
      <?php $stt = 1; $list->data_seek(0); while ($row = $list->fetch_assoc()): ?>
        <div class="list-card">
          <div class="list-card-stt"><?= $stt++ ?></div>
          <div class="list-card-info">
            <div class="list-card-name"><?= htmlspecialchars($row['ho_ten']) ?></div>
            <div class="list-card-sbd">SBD: <?= htmlspecialchars($row['so_bao_danh']) ?></div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

  <?php else: ?>
    <div style="text-align:center; color:#888; padding:30px; background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
      <p style="font-size:16px;">Chưa có dữ liệu trúng tuyển. Hãy upload file Excel để bắt đầu.</p>
    </div>
  <?php endif; ?>

</main>

</body>
</html>