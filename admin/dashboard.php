<?php
session_start();
require '../config.php';

if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Lỗi kết nối CSDL: " . $conn->connect_error);
}

$sql = "
  SELECT ho_ten, lop, so_bao_danh, so_dien_thoai,
         email, nguyen_vong_1, nguyen_vong_2, ngay_dang_ky
  FROM hoc_sinh
  ORDER BY ngay_dang_ky DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="admin_style.css">
</head>
<body>

<header>
  <h1>Admin Dashboard</h1>
  <div class="btn-group">
    <a href="import_trungtuyen.php" class="button">📥 Tải lên trúng tuyển Excel</a>
    <a href="download.php" class="button">📤 Tải danh sách đăng kí</a>
    <a href="deadline.php" class="button">⚙️ Cài đặt hạn đăng ký</a>
    <a href="tohop.php" class="button">📚 Cập nhật tổ hợp</a>
    <a href="logout.php" class="button danger">🚪 Đăng xuất</a>
  </div>
</header>

<main>
  <h2>Danh sách học sinh đã đăng ký</h2>

  <!-- BẢNG DESKTOP -->
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>STT</th>
          <th>Họ tên</th>
          <th>Lớp</th>
          <th>Số báo danh</th>
          <th>SĐT</th>
          <th>Email</th>
          <th>Nguyện vọng 1</th>
          <th>Nguyện vọng 2</th>
          <th>Ngày đăng ký</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php $STT = 1; while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $STT++ ?></td>
              <td><?= htmlspecialchars($row['ho_ten']) ?></td>
              <td><?= htmlspecialchars($row['lop']) ?></td>
              <td><?= htmlspecialchars($row['so_bao_danh']) ?></td>
              <td><?= htmlspecialchars($row['so_dien_thoai']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['nguyen_vong_1']) ?></td>
              <td><?= htmlspecialchars($row['nguyen_vong_2']) ?></td>
              <td><?= $row['ngay_dang_ky'] ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="9" style="text-align:center; color:#888; padding:20px;">Chưa có bản ghi đăng ký.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- CARD MOBILE -->
  <div class="card-list">
    <?php
    if ($result) $result->data_seek(0);
    if ($result && $result->num_rows > 0):
      $STT = 1;
      while ($row = $result->fetch_assoc()):
    ?>
      <div class="card">
        <div class="card-header">
          <div class="card-stt"><?= $STT++ ?></div>
          <div class="card-name"><?= htmlspecialchars($row['ho_ten']) ?></div>      
        </div>
        <div class="card-row">
          <span class="card-label">Lớp</span>
          <span class="card-value"><?= htmlspecialchars($row['lop']) ?></span>
        </div>
        <div class="card-row">
          <span class="card-label">Số báo danh</span>
          <span class="card-value"><?= htmlspecialchars($row['so_bao_danh']) ?></span>
        </div>
        <div class="card-row">
          <span class="card-label">SĐT</span>
          <span class="card-value"><?= htmlspecialchars($row['so_dien_thoai']) ?></span>
        </div>
        <div class="card-row">
          <span class="card-label">Email</span>
          <span class="card-value" style="font-size:12px;"><?= htmlspecialchars($row['email']) ?></span>
        </div>
        <div class="card-row">
          <span class="card-label">Ngày đăng ký</span>
          <span class="card-value" style="font-size:12px;"><?= $row['ngay_dang_ky'] ?></span>
        </div>
        <div class="card-nv">
          NV1: <span><?= htmlspecialchars($row['nguyen_vong_1']) ?></span><br>
          NV2: <span><?= htmlspecialchars($row['nguyen_vong_2']) ?></span>
        </div>
      </div>
    <?php endwhile; else: ?>
      <p style="text-align:center; color:#888;">Chưa có bản ghi đăng ký.</p>
    <?php endif; ?>
  </div>
</main>

</body>
</html>