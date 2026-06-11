<?php
session_start();
require __DIR__ . '/autoload.php';

// ——————— CẤU HÌNH CHUNG ———————
$ten_truong = "Trường THPT Hàm Thuận Nam";
$nam_hoc    = "2025–2026";
$logo_path  = "images/logo-thpt-cent.jpg";
$favicon_path = "images/favicon.png";
// ——————————————————————————

$now = new DateTime();  // Thời điểm hiện tại

// Kết nối CSDL
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Lỗi kết nối cơ sở dữ liệu: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Lấy deadline từ bảng thietlap
$deadline = null;
$res = $conn->query("SELECT gia_tri FROM thietlap WHERE ten='han_dang_ky' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $deadline = new DateTime($row['gia_tri']);
}

// Lấy session thông báo thành công & SBD để sinh QR
$success = $_SESSION['success'] ?? null;
$sbd_qr  = $_SESSION['sbd']     ?? null;
unset($_SESSION['success'], $_SESSION['sbd']);

// Lấy danh sách tổ hợp do Admin cấu hình (bảng to_hop)
$to_hops = [];
$res = $conn->query("SELECT id, ten_to_hop FROM to_hop ORDER BY id");
while ($r = $res->fetch_assoc()) {
    $to_hops[$r['id']] = $r['ten_to_hop'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng ký nguyện vọng lớp 10 – <?= $ten_truong ?></title>
  <link rel="icon" href="<?= $favicon_path ?>">
  <link rel="stylesheet" href="style.css">
</head>
<body>

	<header>
  <div class="admin-bar">
    <?php if (!empty($_SESSION['is_admin'])): ?>
      <a href="admin/dashboard.php">Dashboard</a>
      <a href="admin/logout.php">Logout</a>
    <?php else: ?>
      <a href="admin/login.php">Admin Login</a>
    <?php endif; ?>
  </div>
  <div class="header-inner">
    <img src="<?= $logo_path ?>" alt="Logo trường THPT Hàm Thuận Nam">
    <h1>
      ĐĂNG KÝ NGUYỆN VỌNG TUYỂN SINH LỚP 10<br>
      TRƯỜNG THPT HÀM THUẬN NAM<br>
      NĂM HỌC: 2025 - 2026
    </h1>
  </div>
  </header>

  <main>
    <!-- THÔNG BÁO THÀNH CÔNG -->
    <?php if ($success): ?>
      <div class="alert"><?= htmlspecialchars($success) ?></div>
      <?php if ($sbd_qr): 
        $qr_data = "SBD:$sbd_qr;TR:$ten_truong"; ?>
        <div class="qr" style="text-align:center;">
          <img src="https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=<?= urlencode($qr_data); ?>" alt="QR Code">
          <p>Quét để xác nhận SBD</p>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <!-- HIỂN THỊ ĐẾM NGƯỢC HOẶC THÔNG BÁO HẾT HẠN -->
    <?php if (!$deadline): ?>
      <div class="alert" style="background: #f8d7da; color: #721c24;">
        ⚠️ Chưa cấu hình thời hạn đăng ký trong hệ thống.
      </div>
    <?php elseif ($now > $deadline): ?>
      <div class="alert" style="background: #f8d7da; color: #721c24;">
        🔒 Đăng ký đã kết thúc lúc <?= $deadline->format('d/m/Y H:i'); ?>.
      </div>
    <?php else: ?>
      <div id="countdown" style="text-align:center; font-size: 20px; color: red; margin-bottom: 20px;">
        Thời gian còn lại để đăng ký: <span id="time-remaining"></span>
      </div>

      <!-- FORM ĐĂNG KÝ -->
      <div class="form-container">
        <h2>Phiếu đăng ký nguyện vọng</h2>
        <form method="POST" action="api/dangky.php" id="form-dangky">
          <label>Họ và tên học sinh:</label>
          <input type="text" name="ho_ten" required>

          <label>Lớp 9 hiện tại:</label>
          <input type="text" name="lop" required>

          <label>Số báo danh:</label>
          <input type="text" name="so_bao_danh" required>

          <label>Số điện thoại phụ huynh:</label>
          <input type="text" name="so_dien_thoai" required>

          <label>Email (nếu có):</label>
          <input type="email" name="email">

          <label>Nguyện vọng 1:</label>
          <select name="nv1" required>
            <option value="">-- Chọn nguyện vọng 1 --</option>
            <?php foreach ($to_hops as $id => $label): ?>
              <option value="<?= $id ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>

          <label>Nguyện vọng 2:</label>
          <select name="nv2" required>
            <option value="">-- Chọn nguyện vọng 2 --</option>
            <?php foreach ($to_hops as $id => $label): ?>
              <option value="<?= $id ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>

          <p class="note">* Lưu ý: Nguyện vọng 1 và 2 phải khác nhau.</p>
          <button type="submit">Gửi đăng ký</button>
        </form>
      </div>
    <?php endif; ?>
  </main>

  <footer>
    © <?= date("Y"); ?> – <?= $ten_truong; ?> | Năm học: <?= $nam_hoc; ?><br>
    Website: <a href="https://www.thpthamthuannam.edu.vn" target="_blank" style="color:#004080;"><?= $ten_truong; ?></a>
  </footer>

  <!-- SCRIPT ĐẾM NGƯỢC -->
  <?php if ($deadline && $now < $deadline): ?>
    <script>
      const deadline = new Date("<?= $deadline->format('Y-m-d\TH:i:s') ?>").getTime();
      const countdown = document.getElementById("time-remaining");
      const x = setInterval(function () {
          const now = new Date().getTime();
          const distance = deadline - now;

          if (distance <= 0) {
              clearInterval(x);
              countdown.innerHTML = "ĐÃ HẾT HẠN";
              const formContainer = document.getElementById("form-dangky");
              if (formContainer) formContainer.style.display = "none";
          } else {
              const days = Math.floor(distance / (1000 * 60 * 60 * 24));
              const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
              const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
              const seconds = Math.floor((distance % (1000 * 60)) / 1000);
              countdown.innerHTML = `${days} ngày ${hours} giờ ${minutes} phút ${seconds} giây`;
          }
      }, 1000);
    </script>
  <?php endif; ?>
</body>
</html>
