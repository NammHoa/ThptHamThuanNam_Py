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
unset($_SESSION['success']);

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
  <link rel="stylesheet" href="style.css?v=2">
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
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>
  <?php 
  $error = $_SESSION['error'] ?? null;
  unset($_SESSION['error']);
    if ($error): ?>
      <div class="alert alert-error show"><?= $error ?></div>
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
  <form method="POST" action="api/dangky.php" id="form-dangky" onsubmit="return validateForm()">
    
    <div id="form-error" class="form-alert-error"></div>

    <label>Họ và tên học sinh: <span style="color:red;">*</span></label>
    <input type="text" name="ho_ten">

    <label>Lớp 9 hiện tại: <span style="color:red;">*</span></label>
    <input type="text" name="lop">

    <label>Số báo danh: <span style="color:red;">*</span></label>
    <input type="text" name="so_bao_danh">

    <label>Số điện thoại phụ huynh: <span style="color:red;">*</span></label>
    <input type="text" name="so_dien_thoai">

    <label>Email: <span style="color:red;">*</span></label>
    <input type="email" name="email">

    <label>Nguyện vọng 1: <span style="color:red;">*</span></label>
    <select name="nv1">
      <option value="">-- Chọn nguyện vọng 1 --</option>
      <?php foreach ($to_hops as $id => $label): ?>
        <option value="<?= $id ?>"><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Nguyện vọng 2: <span style="color:red;">*</span></label>
    <select name="nv2">
      <option value="">-- Chọn nguyện vọng 2 --</option>
      <?php foreach ($to_hops as $id => $label): ?>
        <option value="<?= $id ?>"><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>

    <p class="note">* Các trường có dấu <span style="color:red;">*</span> là bắt buộc. Nguyện vọng 1 và 2 phải khác nhau.</p>
    <button type="submit">Gửi đăng ký</button>
  </form>
</div>
    <?php endif; ?>
  </main>

<footer>
  <div class="footer-main">
    <div class="footer-brand">
      <img src="<?= $logo_path ?>" alt="Logo">
      <h3>TRƯỜNG THPT<br>HÀM THUẬN NAM</h3>
    </div>
    <div class="footer-info">
      <p>📍 18 Trần Phú, Xã Hàm Thuận Nam, Tỉnh Lâm Đồng</p>
      <p>📞 02523867255 &nbsp;|&nbsp; 📧 <a href="mailto:c3hamthuannam.binhthuan@moet.edu.vn">c3hamthuannam.binhthuan@moet.edu.vn</a></p>
      <p>🌐 <a href="http://thpthamthuannam.edu.vn" target="_blank">thpthamthuannam.edu.vn</a> &nbsp;|&nbsp; 📘 <a href="https://www.facebook.com/truongthpthamthuannam" target="_blank">facebook.com/truongthpthamthuannam</a></p>
    </div>
  </div>
  <div class="footer-bottom">
    ✨ Sản phẩm được thiết kế bởi <strong>Thầy Huỳnh Minh Châu</strong>
  </div>
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

    <script>
const sessionError = document.querySelector('.alert-error.show');
  if (sessionError) {
    setTimeout(() => {
      sessionError.classList.remove('show');
    }, 3000);
  }

function validateForm() {
  const fields = [
    { name: 'ho_ten',        label: 'Họ và tên học sinh' },
    { name: 'lop',           label: 'Lớp 9 hiện tại' },
    { name: 'so_bao_danh',   label: 'Số báo danh' },
    { name: 'so_dien_thoai', label: 'Số điện thoại phụ huynh' },
  ];

  const errorBox = document.getElementById('form-error');

  for (let f of fields) {
    const el = document.querySelector(`input[name="${f.name}"]`);
    if (!el || !el.value.trim()) {
      showError(errorBox, `⚠️ Vui lòng nhập <strong>${f.label}</strong>.`);
      el && el.focus();
      return false;
    }
  }

  const emailEl = document.querySelector('input[name="email"]');
  const emailVal = emailEl ? emailEl.value.trim() : '';
  if (!emailVal) {
    showError(errorBox, '⚠️ Vui lòng nhập <strong>Email</strong>.');
    emailEl && emailEl.focus();
    return false;
  }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(emailVal)) {
    showError(errorBox, '⚠️ <strong>Email</strong> không hợp lệ. Ví dụ: ten@gmail.com');
    emailEl && emailEl.focus();
    return false;
  }

  const nv1 = document.querySelector('select[name="nv1"]').value;
  const nv2 = document.querySelector('select[name="nv2"]').value;
  if (!nv1) {
    showError(errorBox, '⚠️ Vui lòng chọn <strong>Nguyện vọng 1</strong>.');
    return false;
  }
  if (!nv2) {
    showError(errorBox, '⚠️ Vui lòng chọn <strong>Nguyện vọng 2</strong>.');
    return false;
  }
  if (nv1 === nv2) {
    showError(errorBox, '⚠️ <strong>Nguyện vọng 1 và 2</strong> không được trùng nhau.');
    return false;
  }

  const btn = document.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.innerHTML = '⏳ Đang xử lý...';
  btn.style.background = '#6c757d';
  btn.style.cursor = 'not-allowed';
  return true;
}

function showError(box, msg) {
  box.innerHTML = msg;
  box.classList.add('show');

  setTimeout(() => {
    box.classList.remove('show');
  }, 3000);
}
</script>
</body>
</html>