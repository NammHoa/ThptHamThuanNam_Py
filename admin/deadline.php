<?php
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
    $message = "✅ Đã cập nhật hạn đăng ký thành công!";
    $msgType = 'success';
}

$deadline = "";
$result = $conn->query("SELECT gia_tri FROM thietlap WHERE ten='han_dang_ky' LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $deadline = $row["gia_tri"];
}

$deadlineDisplay = $deadline ? date('d/m/Y H:i', strtotime($deadline)) : '';
$deadlineDateVal = $deadline ? date('Y-m-d', strtotime($deadline)) : '';
$deadlineTimeVal = $deadline ? date('H:i', strtotime($deadline)) : '';

$now        = new DateTime();
$deadlineDT = $deadline ? new DateTime($deadline) : null;
$isExpired  = $deadlineDT && $now > $deadlineDT;
$timeLeft   = '';
if ($deadlineDT && !$isExpired) {
    $diff     = $now->diff($deadlineDT);
    $timeLeft = $diff->days . ' ngày ' . $diff->h . ' giờ ' . $diff->i . ' phút';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thiết lập hạn đăng ký</title>
  <link rel="stylesheet" href="admin_style.css?v=4">
</head>
<body>
<header>
  <h1>Thiết lập thời gian hết hạn đăng ký</h1>
  <div class="btn-group">
    <a href="dashboard.php" class="button">🏠 Dashboard</a>
    <a href="logout.php" class="button danger">🚪 Logout</a>
  </div>
</header>

<main>
<div class="deadline-wrapper">

  <div class="status-card">
    <?php if (!$deadline): ?>
      <div class="status-icon none">⚙️</div>
      <div class="status-info">
        <p class="s-label">Trạng thái</p>
        <p class="s-datetime s-none">Chưa thiết lập</p>
        <p class="s-timeleft s-none-text">Vui lòng cài đặt bên dưới</p>
      </div>
      <span class="status-badge badge-none">Chưa có</span>
    <?php elseif ($isExpired): ?>
      <div class="status-icon expired">🔒</div>
      <div class="status-info">
        <p class="s-label">Đã kết thúc lúc</p>
        <p class="s-datetime"><?= $deadlineDisplay ?></p>
        <p class="s-timeleft s-expired">⛔ Đã hết hạn đăng ký</p>
      </div>
      <span class="status-badge badge-expired">Đã đóng</span>
    <?php else: ?>
      <div class="status-icon active">✅</div>
      <div class="status-info">
        <p class="s-label">Hết hạn lúc</p>
        <p class="s-datetime"><?= $deadlineDisplay ?></p>
        <p class="s-timeleft s-active">⏳ Còn lại: <?= $timeLeft ?></p>
      </div>
      <span class="status-badge badge-active">Đang mở</span>
    <?php endif; ?>
  </div>

  <?php if ($message): ?>
    <div class="result-box result-<?= $msgType ?>" id="result-msg"><?= $message ?></div>
    <script>
      setTimeout(() => {
        const m = document.getElementById('result-msg');
        if (m) { m.style.transition='opacity 0.5s'; m.style.opacity='0'; setTimeout(()=>m.style.display='none',500); }
      }, 3000);
    </script>
  <?php endif; ?>

  <div class="form-card">
    <div class="form-card-title">📅 Cài đặt thời hạn mới</div>
    <form method="post">
      <div class="date-time-grid">
        <div class="field-group">
          <label>📆 Ngày kết thúc</label>
          <input type="date" name="ngay" value="<?= $deadlineDateVal ?>" required>
        </div>
        <div class="field-group">
          <label>🕐 Giờ kết thúc</label>
          <input type="time" name="gio" value="<?= $deadlineTimeVal ?>" required>
        </div>
      </div>
      <button type="submit" class="btn-save-deadline">💾 Lưu hạn đăng ký</button>
    </form>
  </div>

  <div class="deadline-back">
    <a href="dashboard.php">← Quay về trang quản trị</a>
  </div>

</div>
</main>

</body>
</html>