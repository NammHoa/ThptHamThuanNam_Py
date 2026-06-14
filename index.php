<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require __DIR__ . '/autoload.php';

$ten_truong = "Trường THPT Hàm Thuận Nam";
$nam_hoc    = "2025–2026";
$logo_path  = "images/logo-thpt-cent.jpg";
$favicon_path = "images/favicon.png";

$now = new DateTime();

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Lỗi kết nối cơ sở dữ liệu: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$deadline = null;
$res = $conn->query("SELECT gia_tri FROM thietlap WHERE ten='han_dang_ky' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $deadline = new DateTime($row['gia_tri']);
}

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

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
  <link rel="stylesheet" href="style.css?v=10">
  <style>
    .expired-card {
      max-width: 480px; margin: 40px auto;
      background: #fff; border-radius: 16px;
      box-shadow: 0 6px 24px rgba(0,0,0,0.09);
      overflow: hidden;
    }
    .expired-top {
      background: linear-gradient(135deg, #991b1b, #ef4444);
      padding: 32px 28px 28px; text-align: center;
      position: relative; overflow: hidden;
    }
    .expired-top::before {
      content: ''; position: absolute;
      width: 180px; height: 180px; border-radius: 50%;
      background: rgba(255,255,255,0.06);
      top: -60px; right: -40px; pointer-events: none;
    }
    .expired-icon  { font-size: 52px; display: block; margin-bottom: 14px; }
    .expired-title { font-size: 22px; font-weight: 800; color: #fff; margin-bottom: 8px; }
    .expired-time  { font-size: 13px; color: rgba(255,255,255,0.75); }

    .expired-bottom {
      padding: 24px 28px; text-align: center;
    }
    .expired-msg {
      font-size: 14px; color: #555; line-height: 1.8;
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/loading.php'; ?>

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
    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error show"><?= $error ?></div>
    <?php endif; ?>

    <?php if (!$deadline): ?>
      <div class="alert" style="background:#f8d7da;color:#721c24;max-width:520px;margin:40px auto;">
        ⚠️ Chưa cấu hình thời hạn đăng ký trong hệ thống.
      </div>

    <?php elseif ($now > $deadline): ?>
      <div class="expired-card">
        <div class="expired-top">
          <span class="expired-icon">🔒</span>
          <div class="expired-title">Đã hết hạn đăng ký</div>
          <div class="expired-time">Kết thúc lúc: <?= $deadline->format('H:i – d/m/Y') ?></div>
        </div>
        <div class="expired-bottom">
          <p class="expired-msg">
            Hệ thống đã đóng nhận đăng ký nguyện vọng.<br>
            Nếu bạn chưa kịp đăng ký, vui lòng liên hệ nhà trường<br>
            theo thông tin liên lạc bên dưới để được hỗ trợ.
          </p>
        </div>
      </div>

    <?php else: ?>
      <div id="countdown">
        <div class="countdown-label">Thời gian còn lại để đăng ký</div>
        <div class="countdown-timer"></div>
      </div>

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

  <?php if ($deadline && $now < $deadline): ?>
  <script>
    const deadlineTs = new Date("<?= $deadline->format('Y-m-d\TH:i:s') ?>").getTime();
    const countdown  = document.querySelector(".countdown-timer");
    const x = setInterval(function () {
      const now      = new Date().getTime();
      const distance = deadlineTs - now;
      if (distance <= 0) {
        clearInterval(x);
        countdown.innerHTML = "<span style='color:#dc3545;font-size:18px;'>⏰ ĐÃ HẾT HẠN ĐĂNG KÝ</span>";
        const formContainer = document.querySelector(".form-container");
        if (formContainer) formContainer.style.display = "none";
      } else {
        const days    = Math.floor(distance / 86400000);
        const hours   = Math.floor((distance % 86400000) / 3600000);
        const minutes = Math.floor((distance % 3600000)  / 60000);
        const seconds = Math.floor((distance % 60000)    / 1000);
        countdown.innerHTML = `
          <div class="cd-block"><span class="cd-number">${String(days).padStart(2,'0')}</span><span class="cd-label">Ngày</span></div>
          <span class="cd-sep">:</span>
          <div class="cd-block"><span class="cd-number">${String(hours).padStart(2,'0')}</span><span class="cd-label">Giờ</span></div>
          <span class="cd-sep">:</span>
          <div class="cd-block"><span class="cd-number">${String(minutes).padStart(2,'0')}</span><span class="cd-label">Phút</span></div>
          <span class="cd-sep">:</span>
          <div class="cd-block"><span class="cd-number">${String(seconds).padStart(2,'0')}</span><span class="cd-label">Giây</span></div>`;
      }
    }, 1000);
  </script>
  <?php endif; ?>

  <script>
    const sessionError = document.querySelector('.alert-error.show');
    if (sessionError) setTimeout(() => sessionError.classList.remove('show'), 3000);

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
          el && el.focus(); return false;
        }
      }

      const emailEl  = document.querySelector('input[name="email"]');
      const emailVal = emailEl ? emailEl.value.trim() : '';
      if (!emailVal) {
        showError(errorBox, '⚠️ Vui lòng nhập <strong>Email</strong>.');
        emailEl && emailEl.focus(); return false;
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
        showError(errorBox, '⚠️ <strong>Email</strong> không hợp lệ. Ví dụ: ten@gmail.com');
        emailEl && emailEl.focus(); return false;
      }

      const nv1 = document.querySelector('select[name="nv1"]').value;
      const nv2 = document.querySelector('select[name="nv2"]').value;
      if (!nv1) { showError(errorBox, '⚠️ Vui lòng chọn <strong>Nguyện vọng 1</strong>.'); return false; }
      if (!nv2) { showError(errorBox, '⚠️ Vui lòng chọn <strong>Nguyện vọng 2</strong>.'); return false; }
      if (nv1 === nv2) { showError(errorBox, '⚠️ <strong>Nguyện vọng 1 và 2</strong> không được trùng nhau.'); return false; }

      const btn = document.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.innerHTML = '⏳ Đang xử lý...';
      btn.style.background = '#6c757d';
      btn.style.cursor = 'not-allowed';
      window.showOverlay && window.showOverlay('Đang gửi đăng ký...');
      return true;
    }

    function showError(box, msg) {
      box.innerHTML = msg;
      box.classList.add('show');
      setTimeout(() => box.classList.remove('show'), 3000);
    }
  </script>
<button id="btn-top" onclick="window.scrollTo({top:0,behavior:'smooth'})"
  style="display:none;position:fixed;bottom:24px;right:20px;z-index:999;
    width:44px;height:44px;border-radius:50%;border:none;cursor:pointer;
    background:linear-gradient(135deg,#003366,#0066cc);color:#fff;font-size:20px;
    box-shadow:0 4px 14px rgba(0,0,0,0.25);align-items:center;justify-content:center;">↑</button>

<script>
(function(){
  var btn    = document.getElementById('btn-top');
  var footer = document.querySelector('footer');
  window.addEventListener('scroll', function(){
    var scrollY     = window.scrollY;
    var footerTop   = footer ? footer.offsetTop : 999999;
    var btnBottom   = scrollY + window.innerHeight - 80;
    if (scrollY > 300 && btnBottom < footerTop) {
      btn.style.display = 'flex';
    } else {
      btn.style.display = 'none';
    }
  });
})();
</script>
</body>
</html>