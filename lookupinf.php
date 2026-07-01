<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require __DIR__ . '/autoload.php';

$ten_truong   = "Trường THPT Hàm Thuận Nam";
$logo_path    = "images/logo-thpt-cent.jpg";
$favicon_path = "images/favicon.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tra cứu đăng ký – <?= $ten_truong ?></title>
  <link rel="icon" href="<?= $favicon_path ?>">
  <link rel="stylesheet" href="style.css?v=10">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <style>
    .tc-wrap {
      max-width: 540px;
      margin: 32px auto 48px;
      padding: 0 16px;
    }
    .tc-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 16px rgba(0,0,0,0.08);
      overflow: hidden;
    }

    .tc-banner {
      background: linear-gradient(135deg, #003366 0%, #004080 60%, #0066cc 100%);
      padding: 26px 32px;
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .tc-icon {
      width: 48px; height: 48px; flex-shrink: 0;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,0.4);
      background: rgba(255,255,255,0.15);
      display: flex; align-items: center; justify-content: center;
    }
    .tc-icon i { font-size: 22px; color: #fff; }
    .tc-banner h2 {
      color: #fff; font-size: 19px; font-weight: 600;
      margin: 0 0 4px; text-align: left;
    }
    .tc-banner p {
      color: rgba(255,255,255,0.82); font-size: 13px;
      margin: 0; line-height: 1.5; text-align: left;
    }

    .tc-body { padding: 28px 32px 32px; }

    .tc-form { display: flex; flex-direction: column; gap: 14px; }
    .tc-form label {
      font-size: 13px; font-weight: 600;
      color: #374151; margin-bottom: 4px; display: block;
    }
    .tc-form input {
      width: 100%; padding: 11px 14px;
      border: 1px solid #d1d5db; border-radius: 8px;
      font-size: 15px; color: #111827;
      transition: border .15s, box-shadow .15s;
      outline: none;
    }
    .tc-form input:focus {
      border-color: #004080;
      box-shadow: 0 0 0 3px rgba(0,64,128,0.1);
    }
    .tc-btn {
      width: 100%; padding: 12px;
      background: linear-gradient(135deg, #003366, #004080);
      color: #fff; border: none; border-radius: 10px;
      font-size: 15px; font-weight: 600; cursor: pointer;
      margin-top: 4px; transition: opacity .15s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .tc-btn:hover { opacity: 0.9; }
    .tc-btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .tc-btn i { font-size: 18px; }

    .tc-result { margin-top: 24px; display: none; }
    .tc-result.show { display: block; }

    .tc-divider { height: 1px; background: #f1f5f9; margin: 24px 0; }

    .tc-result-title {
      font-size: 13px; font-weight: 600;
      color: #6b7280; text-transform: uppercase;
      letter-spacing: .5px; margin-bottom: 14px;
    }

    .tc-rows {
      display: flex; flex-direction: column;
      border: 1px solid #e5e7eb;
      border-radius: 10px; overflow: hidden;
    }
    .tc-row {
      display: flex; align-items: flex-start;
      padding: 13px 16px;
      border-bottom: 1px solid #f3f4f6;
    }
    .tc-row:last-child { border-bottom: none; }
    .tc-row:nth-child(even) { background: #fafafa; }
    .tc-row-label {
      font-size: 13px; color: #9ca3af;
      width: 130px; flex-shrink: 0; padding-top: 1px;
    }
    .tc-row-value {
      font-size: 14px; color: #111827;
      font-weight: 500; flex: 1; line-height: 1.5;
    }
    .tc-row-value.blue  { color: #1d5fa8; }
    .tc-row-value.bold  { font-size: 15px; font-weight: 700; }
    .tc-row-value.muted { color: #6b7280; font-weight: 400; font-size: 13px; }

    .tc-badge-ok {
      display: inline-flex; align-items: center; gap: 5px;
      background: #d1fae5; color: #065f46;
      font-size: 12px; font-weight: 600;
      padding: 3px 10px; border-radius: 99px;
      border: 1px solid #6ee7b7; margin-bottom: 16px;
    }
    .tc-badge-none {
      display: inline-flex; align-items: center; gap: 5px;
      background: #fef3c7; color: #92400e;
      font-size: 12px; font-weight: 600;
      padding: 3px 10px; border-radius: 99px;
      border: 1px solid #fcd34d; margin-bottom: 16px;
    }

    .tc-error {
      background: #fef2f2; border: 1px solid #fecaca;
      border-radius: 10px; padding: 14px 16px;
      font-size: 13.5px; color: #991b1b;
      display: none; margin-top: 16px;
    }
    .tc-error.show { display: block; }

    /* Chưa đăng ký */
    .tc-empty { text-align: center; padding: 24px 0 8px; }
    .tc-empty-icon { font-size: 40px; margin-bottom: 10px; color: #d1d5db; }
    .tc-empty h3 { font-size: 15px; color: #374151; margin: 0 0 6px; }
    .tc-empty p  { font-size: 13px; color: #9ca3af; margin: 0; line-height: 1.6; }

    .tc-btn-back {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      width: 100%; padding: 11px 20px; margin-top: 20px;
      background: #fff; color: #374151;
      border: 1px solid #d1d5db; border-radius: 10px;
      font-size: 14px; text-decoration: none;
      transition: background .15s;
    }
    .tc-btn-back:hover { background: #f9fafb; color: #374151; text-decoration: none; }
    .tc-btn-back i { font-size: 17px; }

    @media (max-width: 600px) {
      .tc-banner { flex-direction: column; padding: 22px 20px; }
      .tc-banner h2, .tc-banner p { text-align: center; }
      .tc-body { padding: 22px 20px 26px; }
      .tc-row-label { width: 110px; }
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
  <div class="tc-wrap">
    <div class="tc-card">

      <div class="tc-banner">
        <div class="tc-icon">
          <i class="ti ti-search"></i>
        </div>
        <div>
          <h2>Tra cứu đăng ký nguyện vọng</h2>
          <p>Nhập số báo danh và họ tên để kiểm tra thông tin đăng ký của bạn.</p>
        </div>
      </div>

      <div class="tc-body">

        <div class="tc-form">
          <div>
            <label for="inp-sbd">Số báo danh <span style="color:red">*</span></label>
            <input type="text" id="inp-sbd" placeholder="Ví dụ: 120001" maxlength="20">
          </div>
          <div>
            <label for="inp-hoten">Họ và tên học sinh <span style="color:red">*</span></label>
            <input type="text" id="inp-hoten" placeholder="Ví dụ: Nguyễn Văn A">
          </div>
          <button class="tc-btn" id="btn-tracuu" onclick="tracuu()">
            <i class="ti ti-search"></i> Tra cứu
          </button>
        </div>

        <div class="tc-error" id="tc-error"></div>

        <div class="tc-result" id="tc-result"></div>

        <a href="index.php" class="tc-btn-back">
          <i class="ti ti-arrow-left"></i> Quay về trang chủ
        </a>

      </div>
    </div>
  </div>
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

<button id="btn-top" onclick="window.scrollTo({top:0,behavior:'smooth'})"
  style="display:none;position:fixed;bottom:24px;right:20px;z-index:999;
    width:44px;height:44px;border-radius:50%;border:none;cursor:pointer;
    background:linear-gradient(135deg,#003366,#0066cc);color:#fff;font-size:20px;
    box-shadow:0 4px 14px rgba(0,0,0,0.25);align-items:center;justify-content:center;">↑</button>
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

document.getElementById('inp-sbd').addEventListener('keydown', e => { if (e.key === 'Enter') tracuu(); });
document.getElementById('inp-hoten').addEventListener('keydown', e => { if (e.key === 'Enter') tracuu(); });

function showError(msg) {
  const el = document.getElementById('tc-error');
  el.textContent = msg;
  el.classList.add('show');
  document.getElementById('tc-result').classList.remove('show');
}

function tracuu() {
  const sbd   = document.getElementById('inp-sbd').value.trim();
  const hoten = document.getElementById('inp-hoten').value.trim();
  const btn   = document.getElementById('btn-tracuu');
  const errEl = document.getElementById('tc-error');
  const resEl = document.getElementById('tc-result');

  errEl.classList.remove('show');
  resEl.classList.remove('show');

  if (!sbd)   { showError('Vui lòng nhập số báo danh.'); return; }
  if (!hoten) { showError('Vui lòng nhập họ và tên.'); return; }

  btn.disabled = true;
  btn.innerHTML = '<i class="ti ti-loader-2" style="font-size:18px;animation:spin .8s linear infinite;"></i> Đang tra cứu...';

  fetch('api/lookupinf.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `sbd=${encodeURIComponent(sbd)}&ho_ten=${encodeURIComponent(hoten)}`
  })
  .then(r => r.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = '<i class="ti ti-search" style="font-size:18px;"></i> Tra cứu';

    if (!data.success) {
      showError(data.message);
      return;
    }

    resEl.classList.add('show');

    if (data.registered) {
      const d = data.data;
      resEl.innerHTML = `
        <div class="tc-divider"></div>
        <div class="tc-result-title">Kết quả tra cứu</div>
        <span class="tc-badge-ok">
          <i class="ti ti-circle-check" style="font-size:13px;"></i> Đã đăng ký nguyện vọng
        </span>
        <div class="tc-rows">
          <div class="tc-row">
            <span class="tc-row-label">Họ và tên</span>
            <span class="tc-row-value">${escHtml(d.ho_ten)}</span>
          </div>
          <div class="tc-row">
            <span class="tc-row-label">Lớp</span>
            <span class="tc-row-value">${escHtml(d.lop)}</span>
          </div>
          <div class="tc-row">
            <span class="tc-row-label">Số báo danh</span>
            <span class="tc-row-value bold">${escHtml(d.so_bao_danh)}</span>
          </div>
          <div class="tc-row">
            <span class="tc-row-label">Nguyện vọng 1</span>
            <span class="tc-row-value blue">${escHtml(d.nguyen_vong_1)}</span>
          </div>
          <div class="tc-row">
            <span class="tc-row-label">Nguyện vọng 2</span>
            <span class="tc-row-value blue">${escHtml(d.nguyen_vong_2)}</span>
          </div>
          <div class="tc-row">
            <span class="tc-row-label">Ngày đăng ký</span>
            <span class="tc-row-value muted">${escHtml(d.ngay_dang_ky)}</span>
          </div>
        </div>`;
    } else {
      resEl.innerHTML = `
        <div class="tc-divider"></div>
        <span class="tc-badge-none">
          <i class="ti ti-alert-triangle" style="font-size:13px;"></i> Chưa đăng ký
        </span>
        <div class="tc-empty">
          <div class="tc-empty-icon">
            <i class="ti ti-clipboard-x" style="font-size:44px;"></i>
          </div>
          <h3>Chưa tìm thấy thông tin đăng ký</h3>
          <p>Số báo danh <strong>${escHtml(sbd)}</strong> chưa có trong danh sách đăng ký.<br>
          Vui lòng kiểm tra lại hoặc đăng ký nguyện vọng.</p>
        </div>`;
    }
  })
  .catch(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="ti ti-search" style="font-size:18px;"></i> Tra cứu';
    showError('Lỗi kết nối, vui lòng thử lại.');
  });
}

function escHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

</body>
</html>