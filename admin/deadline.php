<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

$message = '';
$msgType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ngay"]) && isset($_POST["gio"])) {
    $deadline = $_POST["ngay"] . ' ' . $_POST["gio"] . ':00';
    $check = $conn->prepare("SELECT * FROM thietlap WHERE ten = 'han_dang_ky' LIMIT 1");
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE thietlap SET gia_tri = ? WHERE ten = 'han_dang_ky'");
    } else {
        $stmt = $conn->prepare("INSERT INTO thietlap (ten, gia_tri) VALUES ('han_dang_ky', ?)");
    }
    $stmt->bind_param("s", $deadline);
    $stmt->execute();
    $message = "Đã cập nhật hạn đăng ký thành công!";
    $msgType = 'success';
}

$deadline = "";
$result = $conn->query("SELECT gia_tri FROM thietlap WHERE ten='han_dang_ky' LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $deadline = $row["gia_tri"];
}

$deadlineDisplay = $deadline ? date('d/m/Y H:i', strtotime($deadline)) : '';
$deadlineDateVal = $deadline ? date('Y-m-d', strtotime($deadline)) : '';
$deadlineTimeVal = $deadline ? date('H:i',   strtotime($deadline)) : '';

$now        = new DateTime();
$deadlineDT = $deadline ? new DateTime($deadline) : null;
$isExpired  = $deadlineDT && $now >= $deadlineDT;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thiết lập hạn đăng ký</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; min-height: 100vh; }

    header {
      background: #004080; color: #fff;
      padding: 20px; text-align: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    header h1 { font-size: 22px; font-weight: bold; margin-bottom: 14px; }
    .btn-group { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
    .btn-header {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: 6px;
      background: rgba(255,255,255,0.15); color: #fff;
      font-size: 14px; font-weight: 500; text-decoration: none;
      border: 1px solid rgba(255,255,255,0.25); transition: all 0.2s;
    }
    .btn-header:hover { background: rgba(255,255,255,0.28); color: #fff; }
    .btn-header-danger { background: rgba(220,53,69,0.7); border-color: rgba(220,53,69,0.5); }
    .btn-header-danger:hover { background: #dc3545; }

    main {
      max-width: 620px; margin: 40px auto; padding: 0 16px;
      display: flex; flex-direction: column; gap: 20px;
    }

    .toast {
      display: flex; align-items: center; gap: 12px;
      padding: 14px 20px; border-radius: 12px;
      font-size: 14px; font-weight: 500;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
      animation: slideIn 0.3s ease; position: relative;
    }
    .toast-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .toast-close {
      position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      font-size: 16px; color: inherit; opacity: 0.6;
    }
    .toast-close:hover { opacity: 1; }
    @keyframes slideIn {
      from { opacity: 0; transform: translateY(-8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .main-card {
      border-radius: 18px; overflow: hidden;
      box-shadow: 0 8px 32px rgba(0,0,0,0.14);
    }

    .card-top {
      padding: 22px 24px 28px;
      position: relative; overflow: hidden;
    }
    .card-top.active  { background: linear-gradient(135deg, #1a7a3c, #28c45e); }
    .card-top.expired { background: linear-gradient(135deg, #991b1b, #ef4444); }
    .card-top.none    { background: linear-gradient(135deg, #92400e, #f59e0b); }

    .card-top::before {
      content: ''; position: absolute;
      width: 220px; height: 220px; border-radius: 50%;
      background: rgba(255,255,255,0.06);
      top: -70px; right: -50px; pointer-events: none;
    }
    .card-top::after {
      content: ''; position: absolute;
      width: 130px; height: 130px; border-radius: 50%;
      background: rgba(255,255,255,0.04);
      bottom: -40px; right: 80px; pointer-events: none;
    }

    .top-row1 {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 14px; position: relative; z-index: 1;
    }
    .top-tag {
      display: inline-block; padding: 4px 12px; border-radius: 20px;
      background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.9);
      font-size: 10px; font-weight: 700; letter-spacing: 2px;
      text-transform: uppercase; border: 1px solid rgba(255,255,255,0.25);
    }
    .top-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; border-radius: 20px;
      background: rgba(255,255,255,0.18); color: #fff;
      font-size: 12px; font-weight: 700;
      border: 1.5px solid rgba(255,255,255,0.35);
    }
    .top-badge-dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: #fff; display: inline-block;
      animation: blink 1.5s ease-in-out infinite;
    }
    @keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.3;} }

    .top-date {
      font-size: 30px; font-weight: 800; color: #fff;
      letter-spacing: -0.5px; line-height: 1.1;
      position: relative; z-index: 1; margin-bottom: 8px;
    }
    .top-sub {
      font-size: 12px; color: rgba(255,255,255,0.65);
      position: relative; z-index: 1; font-weight: 400;
    }
    .top-timer {
      font-size: 13px; color: rgba(255,255,255,0.9); font-weight: 600;
      margin-top: 4px; font-variant-numeric: tabular-nums;
      position: relative; z-index: 1;
    }

    .bar-wrap {
      position: absolute; bottom: 0; left: 0; right: 0;
      height: 5px; background: rgba(255,255,255,0.15);
    }
    .bar-fill {
      height: 100%; background: rgba(255,255,255,0.9);
      transition: width 1s linear; position: relative; overflow: hidden;
    }
    .bar-fill::after {
      content: '';
      position: absolute; top: 0; left: -60px;
      width: 60px; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.9), transparent);
      animation: shimmer 1.8s ease-in-out infinite;
    }
    @keyframes shimmer {
      0%   { left: -60px; }
      100% { left: 100%; }
    }

    .card-bottom { background: #fff; padding: 22px 28px 24px; }
    .form-title {
      font-size: 12px; font-weight: 600; color: #888;
      text-transform: uppercase; letter-spacing: 1px;
      margin-bottom: 16px;
      display: flex; align-items: center; gap: 8px;
    }
    .form-title::after { content: ''; flex: 1; height: 0.5px; background: #e5e7eb; }

    .fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; }
    .field-wrap label {
      display: block; font-size: 11px; font-weight: 600;
      color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;
    }
    .field-wrap input {
      width: 100%; padding: 10px 13px;
      border: 1.5px solid #e5e7eb; border-radius: 9px;
      font-size: 14px; color: #1f2937; background: #f9fafb;
      transition: border-color 0.2s, box-shadow 0.2s; outline: none;
    }
    .field-wrap input:focus {
      border-color: #28a745; box-shadow: 0 0 0 3px rgba(40,167,69,0.1); background: #fff;
    }
    .btn-save {
      width: 100%; padding: 13px;
      background: #004080;
      color: #fff; border: none; border-radius: 10px;
      font-size: 14px; font-weight: 700; cursor: pointer;
      letter-spacing: 0.3px; transition: all 0.2s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-save:hover { background: #003060; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,64,128,0.3); }
    .btn-save:active { transform: translateY(0); }

    .back-link { text-align: center; padding-bottom: 10px; }
    .back-link a { color: #9ca3af; font-size: 13px; text-decoration: none; transition: color 0.2s; }
    .back-link a:hover { color: #004080; }

    @media (max-width: 480px) {
      .fields-grid { grid-template-columns: 1fr; }
      .top-date { font-size: 20px; }
      .card-top { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>

<header>
  <h1>Thiết lập thời gian hết hạn đăng ký nguyện vọng</h1>
  <div class="btn-group">
    <a href="dashboard.php" class="btn-header"><i class="ti ti-home"></i> Dashboard</a>
    <a href="logout.php" class="btn-header btn-header-danger"><i class="ti ti-logout"></i> Đăng xuất</a>
  </div>
</header>

<main>

  <?php if ($message): ?>
  <div class="toast toast-<?= $msgType ?>" id="toast-msg">
    <span><?= $msgType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?></span>
    <button class="toast-close" onclick="dismissToast()">✕</button>
  </div>
  <script>
    function dismissToast() {
      const t = document.getElementById('toast-msg');
      if (!t) return;
      t.style.transition = 'opacity 0.4s';
      t.style.opacity = '0';
      setTimeout(() => t.remove(), 400);
    }
    setTimeout(dismissToast, 3500);
  </script>
  <?php endif; ?>

  <div class="main-card">

    <div class="card-top <?= !$deadline ? 'none' : ($isExpired ? 'expired' : 'active') ?>" id="card-top">
      <?php if (!$deadline): ?>
        <div class="top-row1">
          <span class="top-tag">⚙ Chưa thiết lập</span>
          <span class="top-badge"><span class="top-badge-dot"></span> Chưa có</span>
        </div>
        <div class="top-date" style="font-size:20px;">Vui lòng cài đặt bên dưới</div>

      <?php elseif ($isExpired): ?>
        <div class="top-row1">
          <span class="top-tag">⏰ Hạn đăng ký nguyện vọng</span>
          <span class="top-badge">🔒 Đã đóng</span>
        </div>
        <div class="top-date"><?= $deadlineDisplay ?></div>
        <div class="top-sub">Thời gian kết thúc nhận đăng ký nguyện vọng</div>

      <?php else: ?>
        <div class="top-row1">
          <span class="top-tag">⏰ Hạn đăng ký nguyện vọng</span>
          <span class="top-badge" id="top-badge"><span class="top-badge-dot"></span> Đang mở</span>
        </div>
        <div class="top-date"><?= $deadlineDisplay ?></div>
        <div class="top-sub">Thời gian kết thúc nhận đăng ký nguyện vọng</div>
        <div class="top-timer" id="live-timer">Đang tính...</div>
      <?php endif; ?>

      <?php if ($deadline && !$isExpired): ?>
      <div class="bar-wrap"><div class="bar-fill" id="bar-fill" style="width:100%"></div></div>
      <?php endif; ?>
    </div>

    <!-- FORM -->
    <div class="card-bottom">
      <div class="form-title">Cập nhật thời hạn</div>
      <form method="post">
        <div class="fields-grid">
          <div class="field-wrap">
            <label><i class="ti ti-calendar"></i> Ngày kết thúc</label>
            <input type="date" name="ngay" value="<?= $deadlineDateVal ?>" required>
          </div>
          <div class="field-wrap">
            <label><i class="ti ti-clock"></i> Giờ kết thúc</label>
            <input type="time" name="gio" value="<?= $deadlineTimeVal ?>" required>
          </div>
        </div>
        <button type="submit" class="btn-save">
          <i class="ti ti-device-floppy"></i> Lưu hạn đăng ký
        </button>
      </form>
    </div>

  </div>

  <div class="back-link">
    <a href="dashboard.php">← Quay về trang quản trị</a>
  </div>

</main>

<?php if ($deadlineDT && !$isExpired): ?>
<script>
(function(){
  const deadlineTs = <?= $deadlineDT->getTimestamp() ?> * 1000;
  const serverNow  = <?= $now->getTimestamp() ?> * 1000;
  const totalMs    = deadlineTs - serverNow;
  const timer  = document.getElementById('live-timer');
  const bar    = document.getElementById('bar-fill');
  const badge  = document.getElementById('top-badge');
  const cardTop = document.getElementById('card-top');

  function pad(n){ return String(n).padStart(2,'0'); }

  function tick() {
    const diff = deadlineTs - Date.now();
    if (diff <= 0) {
      if (cardTop) {
        cardTop.className = 'card-top expired';
        cardTop.innerHTML = `
          <div class="top-row1">
            <span class="top-tag">⏰ Hạn đăng ký nguyện vọng</span>
            <span class="top-badge">🔒 Đã đóng</span>
          </div>
          <div class="top-date"><?= $deadlineDisplay ?></div>
          <div class="top-sub">Thời gian kết thúc nhận đăng ký nguyện vọng</div>`;
      }
      if (bar) bar.style.width = '0%';
      return;
    }

    const d = Math.floor(diff / 86400000);
    const h = Math.floor((diff % 86400000) / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);

    const parts = [];
    if (d > 0) parts.push(d + ' ngày');
    parts.push(pad(h) + ' giờ');
    parts.push(pad(m) + ' phút');
    parts.push(pad(s) + ' giây');
    if (timer) timer.textContent = '⏳ Còn lại: ' + parts.join(' ');

    const pct = Math.min(100, Math.max(0, (diff / totalMs) * 100));
    if (bar) bar.style.width = pct + '%';

    setTimeout(tick, 1000);
  }
  tick();
})();
</script>
<?php endif; ?>

</body>
</html>