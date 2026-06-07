<?php
session_start();
require '../config.php';   // Nạp cấu hình DB và thông tin Admin

// ————————————
// 1. Bảo vệ trang: chỉ Admin mới xem được
// ————————————
if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

// ————————————
// 2. Kết nối CSDL
// ————————————
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Lỗi kết nối CSDL: " . $conn->connect_error);
}

// ————————————
// 3. Lấy tất cả bản ghi đăng ký từ bảng `hoc_sinh`
// ————————————
$sql = "
  SELECT 
    ho_ten, lop, so_bao_danh, so_dien_thoai, 
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
  <title>Admin Dashboard</title>
  <style>
    /* ---- Reset & Layout ---- */
    body { font-family: Arial, sans-serif; background: #f0f0f0; margin: 0; padding: 0; }
    header { background: #004080; color: #fff; padding: 20px; text-align: center; }
    header a { color: #fff; margin: 0 10px; text-decoration: none; }
    main { padding: 20px; }
    .button { display: inline-block; padding: 8px 16px; background: #28a745; color: #fff; border-radius: 4px; text-decoration: none; }
    .button:hover { background: #218838; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #e0e0e0; }
  </style>
</head>
<body>

  <!-- HEADER: Tiêu đề và Menu Admin -->
  <header>
    <h1>Admin Dashboard</h1>
    <a href="import_trungtuyen.php" class="button">Upload dữ liệu trúng tuyển Excel</a>
    <a href="download.php" class="button">Download danh sách đăng kí </a>
	<a href="deadline.php" class="button"> ⚙️ Cài đặt hạn đăng ký	</a>
	<a href="tohop.php" class="button"> Cập nhật tổ hợp	</a>
    <a href="logout.php" class="button">Logout</a>
  </header>

  <!-- MAIN: Bảng danh sách đăng ký -->
  <main>
    <h2>Danh sách học sinh đã đăng ký</h2>
    <table>
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
      <?php if ($result && $result->num_rows > 0): ?>
		
        <?php $STT=1; while ($row = $result->fetch_assoc()): ?>
          <tr>
			<td><?php echo $STT++;?></td>
            <td><?php echo htmlspecialchars($row['ho_ten']); ?></td>
            <td><?php echo htmlspecialchars($row['lop']); ?></td>
            <td><?php echo htmlspecialchars($row['so_bao_danh']); ?></td>
            <td><?php echo htmlspecialchars($row['so_dien_thoai']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['nguyen_vong_1']); ?></td>
            <td><?php echo htmlspecialchars($row['nguyen_vong_2']); ?></td>
            <td><?php echo $row['ngay_dang_ky']; ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="8" style="text-align:center;">Chưa có bản ghi đăng ký.</td></tr>
      <?php endif; ?>
    </table>
  </main>

</body>
</html>
