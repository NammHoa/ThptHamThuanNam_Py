<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require __DIR__ . '/autoload.php';

if (empty($_SESSION['dang_ky_thanh_cong'])) {
    header('Location: index.php');
    exit;
}

$data = $_SESSION['dang_ky_thanh_cong'];
unset($_SESSION['dang_ky_thanh_cong']);

$ho_ten = $data['ho_ten'];
$ngay_sinh = $data['ngay_sinh'];
$lop    = $data['lop'];
$nv1    = $data['nv1'];
$nv2    = $data['nv2'];

$ten_truong   = "Trường THPT Hàm Thuận Nam";
$nam_hoc      = "2026–2027";
$logo_path    = "images/logo-thpt-cent.jpg";
$favicon_path = "images/favicon.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng ký thành công – <?= $ten_truong ?></title>
  <link rel="icon" href="<?= $favicon_path ?>">
  <link rel="stylesheet" href="style.css?v=11">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <style>
    .sc-wrap {
      max-width: 540px;
      margin: 28px auto 40px;
      padding: 0 16px;
    }
    .sc-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 16px rgba(0,0,0,0.08);
      overflow: hidden;
    }
    .sc-banner {
      background: #1a7f4b;
      padding: 28px 32px;
      display: flex;
      align-items: center;
      gap: 18px;
    }
    .sc-check {
      width: 48px; height: 48px; flex-shrink: 0;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,0.4);
      background: rgba(255,255,255,0.15);
      display: flex; align-items: center; justify-content: center;
    }
    .sc-check i { font-size: 24px; color: #fff; }
    .sc-banner h2 {
      color: #fff; font-size: 19px; font-weight: 600; margin: 0 0 5px; text-align: left;
    }
    .sc-banner p {
      color: rgba(255,255,255,0.82); font-size: 13px; margin: 0; line-height: 1.5; text-align: left;
    }
    .sc-body { padding: 28px 32px 32px; }

    .sc-greeting { margin-bottom: 24px; }
    .sc-greeting p { font-size: 14px; color: #374151; line-height: 1.75; margin: 0; }
    .sc-greeting strong { color: #003d7a; font-weight: 600; }

    .sc-rows {
      display: flex; flex-direction: column;
      border: 1px solid #e5e7eb;
      border-radius: 10px; overflow: hidden;
    }
    .sc-row {
      display: flex; align-items: flex-start;
      padding: 13px 16px;
      border-bottom: 1px solid #f3f4f6;
    }
    .sc-row:last-child { border-bottom: none; }
    .sc-row:nth-child(even) { background: #fafafa; }
    .sc-row-label {
      font-size: 13px; color: #9ca3af;
      width: 130px; flex-shrink: 0; padding-top: 1px;
    }
    .sc-row-value {
      font-size: 14px; color: #111827;
      font-weight: 500; flex: 1; line-height: 1.5;
    }
    .sc-row-value.blue { color: #1d5fa8; }
    .sc-row-value.bold { font-size: 15px; font-weight: 700; }

    .sc-note {
      margin-top: 20px;
      display: flex; gap: 12px;
      background: #fffbeb;
      border: 1px solid #fde68a;
      border-radius: 10px;
      padding: 14px 16px;
    }
    .sc-note i { font-size: 18px; color: #b45309; flex-shrink: 0; margin-top: 1px; }
    .sc-note strong { display: block; font-size: 13px; font-weight: 600; color: #92400e; margin-bottom: 3px; }
    .sc-note span { font-size: 13px; color: #92400e; line-height: 1.6; }

    .sc-actions { margin-top: 24px; }

    .sc-btn-home {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      width: 100%; padding: 12px 20px;
      background: #fff; color: #374151;
      border: 1px solid #d1d5db; border-radius: 10px;
      font-size: 14.5px; text-decoration: none;
      transition: background .15s;
    }
    .sc-btn-home i { font-size: 18px; }
    .sc-btn-home:hover { background: #f9fafb; color: #374151; text-decoration: none; }

    @media print {
      .admin-bar, .sc-btn-home, footer, #btn-top { display: none !important; }
      body { background: #fff; }
      .sc-wrap { margin: 0; padding: 0; max-width: 100%; }
      .sc-card { box-shadow: none; border-radius: 0; }
    }
    @media (max-width: 600px) {
      .sc-banner { flex-direction: column; padding: 22px 20px; }
      .sc-banner h2, .sc-banner p { text-align: center; }
      .sc-body { padding: 22px 20px 26px; }
      .sc-row-label { width: 105px; }
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
    <a href="index.php">
      <img src="<?= $logo_path ?>" alt="Logo trường THPT Hàm Thuận Nam" style="cursor:pointer;">
    </a>
    <h1>
      ĐĂNG KÝ NGUYỆN VỌNG TUYỂN SINH LỚP 10<br>
      TRƯỜNG THPT HÀM THUẬN NAM<br>
      NĂM HỌC: 2026 - 2027
    </h1>
  </div>
</header>

<main>
  <div class="sc-wrap">
    <div class="sc-card">

      <div class="sc-banner">
        <div class="sc-check">
          <i class="ti ti-check"></i>
        </div>
        <div style="text-align: left;">
          <h2>Đăng ký thành công!</h2>
          <p>Thông tin nguyện vọng đã được ghi nhận.<br>Vui lòng chụp màn hình để lưu lại.</p>
        </div>
      </div>

      <div class="sc-body">

        <div class="sc-greeting">
          <p>Xin chào <strong><?= htmlspecialchars($ho_ten) ?></strong>,<br>
          Bạn đã đăng ký nguyện vọng tuyển sinh lớp 10 thành công. Dưới đây là thông tin của bạn:</p>
        </div>

        <div class="sc-rows">
          <div class="sc-row">
            <span class="sc-row-label">Họ và tên</span>
            <span class="sc-row-value"><?= htmlspecialchars($ho_ten) ?></span>
          </div>
          <div class="sc-row">
            <span class="sc-row-label">Lớp</span>
            <span class="sc-row-value"><?= htmlspecialchars($lop) ?></span>
          </div>
          <div class="sc-row">
            <span class="sc-row-label">Ngày sinh</span>
            <span class="sc-row-value"><?= htmlspecialchars($ngay_sinh) ?></span>
          </div>
          <div class="sc-row">
            <span class="sc-row-label">Nguyện vọng 1</span>
            <span class="sc-row-value blue"><?= htmlspecialchars($nv1) ?></span>
          </div>
          <div class="sc-row">
            <span class="sc-row-label">Nguyện vọng 2</span>
            <span class="sc-row-value blue"><?= htmlspecialchars($nv2) ?></span>
          </div>
        </div>

        <div class="sc-note">
          <i class="ti ti-alert-triangle"></i>
          <div>
            <strong>Lưu ý quan trọng</strong>
            <span>Mỗi số báo danh chỉ được đăng ký một lần. Nếu cần thay đổi nguyện vọng, vui lòng liên hệ trực tiếp với nhà trường.</span>
          </div>
        </div>

        <div class="sc-actions">
          <a href="index.php" class="sc-btn-home">
            <i class="ti ti-arrow-left"></i> Quay về trang chủ
          </a>
        </div>

      </div>
    </div>
  </div>
</main>

<footer>
  <div class="footer-accent"></div>
  <div class="footer-body">

    <div class="footer-brand">
      <img src="<?= $logo_path ?>" alt="Logo">
      <div class="footer-brand-text">
        <h3>TRƯỜNG THPT<br>HÀM THUẬN NAM</h3>
        <p>Môi trường giáo dục xanh – sạch – đẹp – an toàn</p>
      </div>
    </div>

    <div class="footer-info">
      <div class="footer-info-item">
        <i class="ti ti-map-pin"></i>
        <span>18 Trần Phú, Xã Hàm Thuận Nam, Tỉnh Lâm Đồng</span>
      </div>
      <div class="footer-info-item">
        <i class="ti ti-phone"></i>
        <span>02523867255</span>
      </div>
      <div class="footer-info-item">
        <i class="ti ti-mail"></i>
        <a href="mailto:c3hamthuannam.binhthuan@moet.edu.vn">c3hamthuannam.binhthuan@moet.edu.vn</a>
      </div>
      <div class="footer-info-item">
        <i class="ti ti-world"></i>
        <a href="http://thpthamthuannam.edu.vn" target="_blank">thpthamthuannam.edu.vn</a>
      </div>
    </div>

    <div class="footer-fb">
      <div class="fb-page"
        data-href="https://www.facebook.com/truongthpthamthuannam"
        data-tabs="timeline"
        data-width="260"
        data-height="200"
        data-small-header="true"
        data-adapt-container-width="false"
        data-hide-cover="false"
        data-show-facepile="false">
      </div>
    </div>

  </div>
  <div class="footer-bottom">
    <span>© <?= date('Y') ?> Trường THPT Hàm Thuận Nam. Thiết kế bởi <strong>Thầy Huỳnh Minh Châu</strong></span>
  </div>
</footer>

<div id="fb-root"></div>
<script async defer crossorigin="anonymous"
  src="https://connect.facebook.net/vi_VN/sdk.js#xfbml=1&version=v19.0">
</script>

<button id="btn-top" onclick="window.scrollTo({top:0,behavior:'smooth'})"
  style="display:none;position:fixed;bottom:24px;right:20px;z-index:999;
    width:44px;height:44px;border-radius:50%;border:none;cursor:pointer;
    background:linear-gradient(135deg,#003366,#0066cc);color:#fff;
    box-shadow:0 4px 14px rgba(0,0,0,0.25);align-items:center;justify-content:center;">
  <i class="ti ti-arrow-up" style="font-size:20px;"></i>
</button>
<script>
(function(){
  var btn    = document.getElementById('btn-top');
  var footer = document.querySelector('footer');
  window.addEventListener('scroll', function(){
    var scrollY   = window.scrollY;
    var footerTop = footer ? footer.offsetTop : 999999;
    var btnBottom = scrollY + window.innerHeight - 80;
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