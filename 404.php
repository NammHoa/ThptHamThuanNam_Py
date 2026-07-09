<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require __DIR__ . '/autoload.php';

http_response_code(404); // Quan trọng — báo đúng mã lỗi 404

$ten_truong   = "Trường THPT Hàm Thuận Nam";
$logo_path    = "images/logo-thpt-cent.jpg";
$favicon_path = "images/favicon.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 – Không tìm thấy trang</title>
  <link rel="icon" href="<?= $favicon_path ?>">
  <link rel="stylesheet" href="style.css?v=11">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
  <style>
    .err-wrap {
      max-width: 480px;
      margin: 48px auto 60px;
      padding: 0 16px;
      text-align: center;
    }
    .err-code {
      font-size: 96px;
      font-weight: 800;
      color: #003d7a;
      line-height: 1;
      margin: 0 0 8px;
      letter-spacing: -4px;
    }
    .err-icon {
      font-size: 52px;
      color: #d1d5db;
      margin-bottom: 16px;
    }
    .err-title {
      font-size: 22px;
      font-weight: 700;
      color: #1e293b;
      margin: 0 0 10px;
    }
    .err-desc {
      font-size: 14px;
      color: #64748b;
      line-height: 1.7;
      margin: 0 0 28px;
    }
    .err-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    }
    .btn-home {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 11px 24px;
      background: #003d7a; color: #fff;
      border-radius: 10px; text-decoration: none;
      font-size: 14px; font-weight: 600;
      transition: background .15s;
    }
    .btn-home:hover { background: #002d5e; color: #fff; text-decoration: none; }
    .btn-tracuu {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 11px 24px;
      background: #fff; color: #003d7a;
      border: 2px solid #003d7a;
      border-radius: 10px; text-decoration: none;
      font-size: 14px; font-weight: 600;
      transition: background .15s, color .15s;
    }
    .btn-tracuu:hover { background: #003d7a; color: #fff; text-decoration: none; }
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
    <a href="index.php"><img src="<?= $logo_path ?>" alt="Logo" style="cursor:pointer;"></a>
    <h1>
      ĐĂNG KÝ NGUYỆN VỌNG TUYỂN SINH LỚP 10<br>
      TRƯỜNG THPT HÀM THUẬN NAM<br>
      NĂM HỌC: 2025 - 2026
    </h1>
  </div>
</header>

<main>
  <div class="err-wrap">
    <div class="err-icon">
      <i class="ti ti-file-off"></i>
    </div>
    <div class="err-code">404</div>
    <h1 class="err-title">Không tìm thấy trang</h1>
    <p class="err-desc">
      Trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.<br>
      Vui lòng kiểm tra lại đường dẫn hoặc quay về trang chủ.
    </p>
    <div class="err-actions">
      <a href="index.php" class="btn-home">
        <i class="ti ti-home"></i> Về trang chủ
      </a>
      <a href="lookupinf.php" class="btn-tracuu">
        <i class="ti ti-search"></i> Tra cứu nguyện vọng
      </a>
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
        data-tabs="timeline" data-width="260" data-height="200"
        data-small-header="true" data-adapt-container-width="false"
        data-hide-cover="false" data-show-facepile="false">
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
  var btn = document.getElementById('btn-top');
  var footer = document.querySelector('footer');
  window.addEventListener('scroll', function(){
    var scrollY = window.scrollY;
    var footerTop = footer ? footer.offsetTop : 999999;
    var btnBottom = scrollY + window.innerHeight - 80;
    if (scrollY > 300 && btnBottom < footerTop) btn.style.display = 'flex';
    else btn.style.display = 'none';
  });
})();
</script>

</body>
</html>