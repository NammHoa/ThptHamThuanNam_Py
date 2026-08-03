<?php
session_start();
require '../config.php';

if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Lỗi kết nối CSDL: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$resultMsg  = '';
$resultType = '';

// Xóa 1 học sinh
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);

    // Lấy cả ho_ten và email trước khi xóa
    $r = $conn->prepare("SELECT ho_ten, email FROM hoc_sinh WHERE id = ?");
    $r->bind_param("i", $id); $r->execute();
    $hs = $r->get_result()->fetch_assoc(); $r->close();

    // Xóa trong hoc_sinh
    $stmt = $conn->prepare("DELETE FROM hoc_sinh WHERE id = ?");
    $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();

    // Xóa email chưa gửi trong mail_queue
    if ($hs && !empty($hs['email'])) {
        $stmt = $conn->prepare("DELETE FROM mail_queue WHERE email = ? AND sent = 0");
        $stmt->bind_param("s", $hs['email']);
        $stmt->execute(); $stmt->close();
    }

    $_SESSION['dash_msg'] = 'deleted';
    session_write_close();
    header("Location: dashboard.php"); exit;
}

// Xóa tất cả
if (isset($_GET['delete_all'])) {
    $conn->query("DELETE FROM hoc_sinh");
    $_SESSION['dash_msg'] = 'deleted_all';
    session_write_close();
    header("Location: dashboard.php"); exit;
}

// Sửa học sinh
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id    = intval($_POST['edit_id']);
    $ten   = trim($_POST['edit_ho_ten']);
    $lop   = trim($_POST['edit_lop']);
    $sdt   = trim($_POST['edit_sdt']);
    $email = trim($_POST['edit_email']);
    $nv1   = trim($_POST['edit_nv1']);
    $nv2   = trim($_POST['edit_nv2']);
    $stmt  = $conn->prepare("UPDATE hoc_sinh SET ho_ten=?, lop=?, so_dien_thoai=?, email=?, nguyen_vong_1=?, nguyen_vong_2=? WHERE id=?");
    $stmt->bind_param("ssssssi", $ten, $lop, $sdt, $email, $nv1, $nv2, $id);
    $stmt->execute(); $stmt->close();
    $_SESSION['dash_msg'] = 'edited';
    session_write_close();
    header("Location: dashboard.php"); exit;
}

if (isset($_SESSION['dash_msg'])) {
    $m = $_SESSION['dash_msg']; unset($_SESSION['dash_msg']);
    if ($m === 'deleted')     { $resultMsg = "Đã xoá học sinh thành công.";       $resultType = 'success'; }
    if ($m === 'deleted_all') { $resultMsg = "Đã xoá toàn bộ danh sách.";         $resultType = 'success'; }
    if ($m === 'edited')      { $resultMsg = "Đã cập nhật thông tin thành công."; $resultType = 'success'; }
}

// Metric
$tongHS     = $conn->query("SELECT COUNT(*) FROM hoc_sinh")->fetch_row()[0];
$homNay     = $conn->query("SELECT COUNT(*) FROM hoc_sinh WHERE DATE(ngay_dang_ky) = CURDATE()")->fetch_row()[0];
$tongTT     = $conn->query("SELECT COUNT(*) FROM danh_sach_trung_tuyen")->fetch_row()[0];
$chuaDK     = max(0, $tongTT - $tongHS);
$phanTramDK = $tongTT > 0 ? round(($tongHS / $tongTT) * 100) : 0;

// Thống kê NV
$statsNV1 = []; $statsNV2 = [];
$r = $conn->query("SELECT nguyen_vong_1, COUNT(*) as cnt FROM hoc_sinh GROUP BY nguyen_vong_1 ORDER BY cnt DESC");
while ($row = $r->fetch_assoc()) $statsNV1[$row['nguyen_vong_1']] = $row['cnt'];
$r = $conn->query("SELECT nguyen_vong_2, COUNT(*) as cnt FROM hoc_sinh GROUP BY nguyen_vong_2 ORDER BY cnt DESC");
while ($row = $r->fetch_assoc()) $statsNV2[$row['nguyen_vong_2']] = $row['cnt'];
$allTH = array_unique(array_merge(array_keys($statsNV1), array_keys($statsNV2)));

// Thống kê 7 ngày
$statsNgay = [];
$r = $conn->query("SELECT DATE(ngay_dang_ky) as ngay, COUNT(*) as cnt FROM hoc_sinh WHERE ngay_dang_ky >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(ngay_dang_ky) ORDER BY ngay ASC");
while ($row = $r->fetch_assoc()) $statsNgay[$row['ngay']] = $row['cnt'];
$ngayLabels = []; $ngayCounts = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $ngayLabels[] = date('d/m', strtotime($d));
    $ngayCounts[] = $statsNgay[$d] ?? 0;
}

// Search + sort + phân trang
$search  = trim($_GET['search']  ?? '');
$page    = max(1, intval($_GET['page'] ?? 1));
$limit   = 20; $offset = ($page - 1) * $limit;

$sortAllowed = ['lop', 'ho_ten', 'so_bao_danh'];
$sortBy  = in_array($_GET['sort_by'] ?? '', $sortAllowed) ? $_GET['sort_by'] : '';
$sortDir = 'ASC';
$orderSQL = match($sortBy) {
    'lop'    => "lop ASC, CONVERT(SUBSTRING_INDEX(ho_ten,' ',-1) USING utf8mb4) COLLATE utf8mb4_unicode_ci ASC",
    default  => "ngay_dang_ky DESC",
};

if ($sortBy === 'ho_ten') {
    if ($search !== '') {
        $like = "%$search%";
        $r = $conn->prepare("SELECT COUNT(*) FROM hoc_sinh WHERE ho_ten LIKE ? OR lop LIKE ? OR email LIKE ? OR ngay_sinh LIKE ?");
        $r->bind_param("ssss", $like, $like, $like, $like); $r->execute(); $r->bind_result($total); $r->fetch(); $r->close();
        $stmt = $conn->prepare("SELECT * FROM hoc_sinh WHERE ho_ten LIKE ? OR lop LIKE ? OR email LIKE ? OR ngay_sinh LIKE ?");
        $stmt->bind_param("ssss", $like, $like, $like, $like);
    } else {
        $r = $conn->query("SELECT COUNT(*) FROM hoc_sinh"); $total = $r->fetch_row()[0];
        $stmt = $conn->prepare("SELECT * FROM hoc_sinh");
    }
    $stmt->execute();
    $allRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    usort($allRows, function($a, $b) {
        $tenA = mb_strtolower(trim(strrchr(trim($a['ho_ten']),' ')),'UTF-8') ?: mb_strtolower($a['ho_ten'],'UTF-8');
        $tenB = mb_strtolower(trim(strrchr(trim($b['ho_ten']),' ')),'UTF-8') ?: mb_strtolower($b['ho_ten'],'UTF-8');
        $cmp  = strcmp($tenA, $tenB);
        if ($cmp === 0) $cmp = strcmp(mb_strtolower($a['ho_ten'],'UTF-8'), mb_strtolower($b['ho_ten'],'UTF-8'));
        return $cmp;
    });
    $totalPages = ceil($total / $limit);
    $allRows    = array_slice($allRows, $offset, $limit);
    $listResult = new class($allRows) {
        private $rows; private $pos = 0;
        public function __construct($rows) { $this->rows = $rows; }
        public function fetch_assoc() { return $this->rows[$this->pos++] ?? null; }
        public function data_seek($p) { $this->pos = $p; }
    };
} else {
    if ($search !== '') {
        $like = "%$search%";
        $r = $conn->prepare("SELECT COUNT(*) FROM hoc_sinh WHERE ho_ten LIKE ? OR lop LIKE ? OR email LIKE ? OR ngay_sinh LIKE ?");
        $r->bind_param("ssss", $like, $like, $like, $like); $r->execute(); $r->bind_result($total); $r->fetch(); $r->close();
        $list = $conn->prepare("SELECT * FROM hoc_sinh WHERE ho_ten LIKE ? OR lop LIKE ? OR email LIKE ? OR ngay_sinh LIKE ? ORDER BY $orderSQL LIMIT ? OFFSET ?");
        $list->bind_param("ssssii", $like, $like, $like, $like, $limit, $offset);
    } else {
        $r = $conn->query("SELECT COUNT(*) FROM hoc_sinh"); $total = $r->fetch_row()[0];
        $list = $conn->prepare("SELECT * FROM hoc_sinh ORDER BY $orderSQL LIMIT ? OFFSET ?");
        $list->bind_param("ii", $limit, $offset);
    }
    $list->execute();
    $listResult = $list->get_result();
    $totalPages = ceil($total / $limit);
}

$tohops = [];
$r = $conn->query("SELECT ten_to_hop FROM to_hop ORDER BY id");
while ($row = $r->fetch_assoc()) $tohops[] = $row['ten_to_hop'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="admin_style.css?v=3">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
  <style>
    .metric-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px; margin-bottom:20px; }
    .metric-card { background:#fff; border-radius:10px; padding:15px 18px; box-shadow:0 2px 8px rgba(0,0,0,0.07); }
    .metric-card .label { font-size:13px; color:#888; margin:0 0 5px; }
    .metric-card .value { font-size:26px; font-weight:500; color:#004080; margin:0; }
    .metric-card .sub   { font-size:12px; color:#aaa; margin:4px 0 0; }
    .stat-section { background:#fff; border-radius:10px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.07); }
    .stat-section h3 { color:#004080; margin:0 0 15px; font-size:16px; display:flex; align-items:center; gap:8px; }
    .chart-grid { display:grid; grid-template-columns:1fr; gap:16px; margin-bottom:20px; }
    .chart-card { background:#fff; border-radius:10px; padding:18px 20px; box-shadow:0 2px 8px rgba(0,0,0,0.07); }
    .chart-card h3 { color:#004080; margin:0 0 14px; font-size:15px; display:flex; align-items:center; gap:8px; }
    .progress-bar-wrap { margin-bottom:10px; }
    .progress-bar-label { display:flex; justify-content:space-between; font-size:13px; color:#555; margin-bottom:5px; }
    .progress-bar-track { background:#f0f0f0; border-radius:99px; height:10px; overflow:hidden; }
    .progress-bar-fill { height:100%; border-radius:99px; background:linear-gradient(90deg,#004080,#0066cc); transition:width .8s ease; }
    .progress-bar-fill.green { background:linear-gradient(90deg,#28a745,#20c050); }
    .search-bar { display:flex; gap:10px; align-items:center; background:#fff; border-radius:10px; padding:14px 18px; margin-bottom:15px; box-shadow:0 2px 8px rgba(0,0,0,0.07); flex-wrap:wrap; }
    .search-bar input { flex:1; min-width:200px; padding:9px 14px; border:1px solid #ccc; border-radius:8px; font-size:14px; }
    .search-bar input:focus { border-color:#004080; outline:none; }
    .search-bar select { padding:9px 12px; border:1px solid #ccc; border-radius:8px; font-size:14px; color:#333; }
    .btn-search { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; background:#004080; color:#fff; border:none; border-radius:8px; font-size:14px; cursor:pointer; }
    .btn-search:hover { background:#003060; }
    .btn-reset  { display:inline-flex; align-items:center; gap:6px; padding:9px 14px; background:#e0e0e0; color:#555; border:none; border-radius:8px; font-size:14px; cursor:pointer; text-decoration:none; }
    .btn-reset:hover { background:#ccc; text-decoration:none; }
    .btn-delete-all { display:inline-flex; align-items:center; gap:6px; padding:9px 16px; background:transparent; color:#dc3545; border:1.5px solid #dc3545; border-radius:8px; font-size:14px; cursor:pointer; text-decoration:none; transition:all .2s; }
    .btn-delete-all:hover { background:#dc3545; color:#fff; text-decoration:none; }
    .result-box { display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:8px; margin-bottom:15px; font-size:14px; }
    .result-success { background:#d4edda; color:#155724; border-left:4px solid #28a745; }
    .search-info { font-size:13px; color:#888; margin-bottom:10px; padding:8px 12px; background:#f8f9ff; border-radius:8px; border-left:3px solid #004080; }
    .table-wrapper { background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.07); overflow-x:auto; }
    table { width:100%; border-collapse:collapse; min-width:750px; }
    thead th { background:linear-gradient(135deg,#003366,#004080); color:#fff; padding:11px 10px; text-align:left; font-size:13px; }
    tbody td { padding:10px; border-bottom:1px solid #f0f0f0; font-size:13px; vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#f8f9ff; }
    .btn-edit-row { display:inline-flex; align-items:center; gap:4px; padding:4px 9px; background:transparent; color:#007bff; border:1px solid #007bff; border-radius:5px; font-size:11px; cursor:pointer; margin-right:3px; transition:all 0.2s; }
    .btn-edit-row:hover { background:#007bff; color:#fff; }
    .btn-del-row  { display:inline-flex; align-items:center; gap:4px; padding:4px 9px; background:transparent; color:#dc3545; border:1px solid #dc3545; border-radius:5px; font-size:11px; cursor:pointer; transition:all 0.2s; text-decoration:none; }
    .btn-del-row:hover { background:#dc3545; color:#fff; text-decoration:none; }
    .pagination { display:flex; justify-content:center; gap:6px; padding:14px 0; flex-wrap:wrap; }
    .pagination a, .pagination span { padding:6px 12px; border-radius:6px; font-size:13px; text-decoration:none; border:1px solid #ddd; color:#004080; transition:all 0.2s; }
    .pagination a:hover { background:#004080; color:#fff; border-color:#004080; }
    .pagination .active  { background:#004080; color:#fff; border-color:#004080; font-weight:bold; }
    .pagination .disabled { color:#ccc; cursor:default; }
    .pagination-info { text-align:center; font-size:12px; color:#888; margin-top:-6px; margin-bottom:10px; }
    .stat-table { width:100%; border-collapse:collapse; font-size:13px; }
    .stat-table th { background:#f0f4f8; color:#004080; padding:9px 12px; text-align:left; font-weight:500; }
    .stat-table td { padding:9px 12px; border-bottom:1px solid #f0f0f0; }
    .stat-table tr:last-child td { border-bottom:none; }
    .badge-nv1 { display:inline-block; padding:2px 8px; background:#e3f0fb; color:#1565c0; border-radius:12px; font-size:12px; font-weight:500; }
    .badge-nv2 { display:inline-block; padding:2px 8px; background:#e3f7f0; color:#1b7a5a; border-radius:12px; font-size:12px; font-weight:500; }
    .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; padding:15px; }
    .modal-overlay.active { display:flex; }
    .modal { background:#fff; border-radius:12px; padding:25px; width:100%; max-width:520px; box-shadow:0 10px 40px rgba(0,0,0,0.2); animation:slideIn 0.2s ease; max-height:90vh; overflow-y:auto; }
    @keyframes slideIn { from{transform:translateY(-20px);opacity:0} to{transform:translateY(0);opacity:1} }
    .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; padding-bottom:12px; border-bottom:2px solid #f0f4f8; }
    .modal-title { font-size:16px; font-weight:bold; color:#004080; margin:0; display:flex; align-items:center; gap:8px; }
    .modal-close { background:none; border:none; font-size:22px; cursor:pointer; color:#888; }
    .modal label { display:block; font-weight:bold; color:#444; font-size:13px; margin:10px 0 4px; }
    .modal input, .modal select { width:100%; padding:8px 12px; border:1px solid #ccc; border-radius:7px; font-size:14px; box-sizing:border-box; }
    .modal input:focus, .modal select:focus { border-color:#004080; outline:none; }
    .modal-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .modal-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:15px; }
    .btn-cancel { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#e0e0e0; color:#555; border:none; border-radius:7px; font-size:13px; cursor:pointer; }
    .btn-save   { display:inline-flex; align-items:center; gap:6px; padding:8px 20px; background:linear-gradient(135deg,#28a745,#20c050); color:#fff; border:none; border-radius:7px; font-size:13px; font-weight:bold; cursor:pointer; }
    .confirm-modal { max-width:420px; text-align:center; }
    .confirm-icon { font-size:48px; color:#dc3545; margin-bottom:12px; }
    .confirm-title { font-size:18px; font-weight:700; color:#1e293b; margin-bottom:8px; }
    .confirm-desc  { font-size:14px; color:#64748b; margin-bottom:20px; line-height:1.6; }
    .btn-confirm-delete { display:inline-flex; align-items:center; gap:6px; padding:10px 24px; background:#dc3545; color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; }
    .btn-confirm-delete:hover { background:#c82333; text-decoration:none; }
    #edit-error { display:none; background:#fef2f2; border-left:3px solid #dc3545; color:#991b1b; padding:10px 14px; border-radius:6px; font-size:13px; margin-bottom:12px; }
    /* Card mobile — giữ nguyên từ bản gốc */
    .card-list { display:none; }
    .card { background:#fff; border-radius:10px; padding:14px; margin-bottom:10px; box-shadow:0 2px 6px rgba(0,0,0,0.07); }
    .card-header { display:flex; align-items:center; gap:10px; margin-bottom:8px; padding-bottom:8px; border-bottom:1px solid #f0f0f0; }
    .card-stt { background:#004080; color:#fff; border-radius:50%; width:30px; height:30px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold; flex-shrink:0; }
    .card-name { font-weight:bold; color:#004080; font-size:15px; flex:1; }
    .card-row { display:flex; justify-content:space-between; padding:4px 0; font-size:13px; border-bottom:1px solid #f8f8f8; }
    .card-label { color:#888; }
    .card-nv { margin-top:8px; background:#f0f4f8; border-radius:6px; padding:8px 12px; font-size:12px; line-height:1.8; }
    .card-actions { display:flex; gap:8px; margin-top:10px; }
    .card-actions .btn-edit-row, .card-actions .btn-del-row { flex:1; justify-content:center; padding:7px; font-size:12px; }
    @media (max-width:600px) {
      .table-wrapper { display:none; }
      .card-list { display:block; }
      .metric-grid { grid-template-columns:1fr 1fr; }
      .modal-row { grid-template-columns:1fr; }
      .chart-grid { display:none; }
    }
  </style>
</head>
<body>

<header>
  <h1>Admin Dashboard</h1>
  <div class="btn-group">
    <a href="import_trungtuyen.php" class="btn-header"><i class="ti ti-upload"></i> Tải lên Excel</a>
    <a href="download.php" class="btn-header"><i class="ti ti-download"></i> Tải danh sách</a>
    <a href="deadline.php" class="btn-header"><i class="ti ti-clock"></i> Hạn đăng ký</a>
    <a href="tohop.php" class="btn-header"><i class="ti ti-books"></i> Tổ hợp</a>
    <a href="logout.php" class="btn-header btn-header-danger"><i class="ti ti-logout"></i> Đăng xuất</a>
  </div>
</header>

<main>

  <?php if ($resultMsg): ?>
  <div id="result-msg" style="position:fixed;top:20px;right:20px;z-index:9999;display:flex;align-items:center;gap:10px;padding:14px 20px;border-radius:10px;font-size:14px;font-weight:500;box-shadow:0 4px 16px rgba(0,0,0,0.15);background:#d4edda;color:#155724;border-left:4px solid #28a745;min-width:280px;animation:slideInRight 0.3s ease;">
    <i class="ti ti-circle-check" style="font-size:20px;color:#28a745;"></i>
    <span><?= htmlspecialchars($resultMsg) ?></span>
  </div>
  <style>@keyframes slideInRight{from{opacity:0;transform:translateX(60px)}to{opacity:1;transform:translateX(0)}}</style>
  <script>
    setTimeout(()=>{
      const m=document.getElementById('result-msg');
      if(m){m.style.transition='opacity 0.5s,transform 0.5s';m.style.opacity='0';m.style.transform='translateX(60px)';setTimeout(()=>m.parentNode&&m.parentNode.removeChild(m),500);}
    },3000);
  </script>
  <?php endif; ?>

  <!-- Metric cards -->
  <div class="metric-grid">
    <div class="metric-card"><p class="label">Tổng đăng ký</p><p class="value"><?= $tongHS ?></p><p class="sub">học sinh</p></div>
    <div class="metric-card"><p class="label">Hôm nay</p><p class="value"><?= $homNay ?></p><p class="sub">đăng ký mới</p></div>
    <div class="metric-card"><p class="label">Trúng tuyển</p><p class="value"><?= $tongTT ?></p><p class="sub">trong danh sách</p></div>
    <div class="metric-card"><p class="label">Chưa đăng ký</p><p class="value" style="color:#dc3545;"><?= $chuaDK ?></p><p class="sub">học sinh còn lại</p></div>
  </div>

  <!-- Tiến độ -->
  <div class="stat-section">
    <h3><i class="ti ti-activity"></i> Tiến độ đăng ký</h3>
    <div class="progress-bar-wrap">
      <div class="progress-bar-label">
        <span>Đã đăng ký: <strong><?= $tongHS ?> / <?= $tongTT ?> học sinh</strong></span>
        <span><strong><?= $phanTramDK ?>%</strong></span>
      </div>
      <div class="progress-bar-track">
        <div class="progress-bar-fill green" style="width:<?= $phanTramDK ?>%;"></div>
      </div>
    </div>
    <div style="font-size:13px;color:#888;margin-top:6px;">Còn <strong style="color:#dc3545;"><?= $chuaDK ?></strong> học sinh chưa đăng ký nguyện vọng.</div>
  </div>

  <!-- Biểu đồ 7 ngày -->
  <div class="chart-grid">
    <div class="chart-card">
      <h3><i class="ti ti-calendar-stats"></i> Đăng ký 7 ngày gần nhất</h3>
      <div style="position:relative;height:200px;"><canvas id="ngayChart"></canvas></div>
    </div>
  </div>

  <!-- Biểu đồ tổ hợp -->
  <?php if (!empty($allTH)): ?>
  <div class="stat-section">
    <h3><i class="ti ti-chart-bar"></i> Thống kê đăng ký theo tổ hợp</h3>
    <div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:10px;font-size:12px;color:#888;">
      <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:10px;border-radius:2px;background:#378ADD;"></span>Nguyện vọng 1</span>
      <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:10px;border-radius:2px;background:#1D9E75;"></span>Nguyện vọng 2</span>
    </div>
    <div style="position:relative;width:100%;height:260px;margin-bottom:20px;"><canvas id="nvChart"></canvas></div>
    <table class="stat-table">
      <thead><tr><th>Tổ hợp môn</th><th style="text-align:center;">NV1</th><th style="text-align:center;">NV2</th><th style="text-align:center;">Tổng</th></tr></thead>
      <tbody>
        <?php foreach ($allTH as $th): $n1=$statsNV1[$th]??0; $n2=$statsNV2[$th]??0; ?>
        <tr>
          <td><?= htmlspecialchars($th) ?></td>
          <td style="text-align:center;"><span class="badge-nv1"><?= $n1 ?></span></td>
          <td style="text-align:center;"><span class="badge-nv2"><?= $n2 ?></span></td>
          <td style="text-align:center;font-weight:500;color:#004080;"><?= $n1+$n2 ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- Search + sort + xóa tất cả -->
  <form method="GET" class="search-bar">
    <input type="text" name="search"
           placeholder="Tìm theo họ tên, lớp, ngày sinh hoặc email..."
           value="<?= htmlspecialchars($search) ?>">
    <select name="sort_by">
      <option value="">Mới đăng ký nhất</option>
      <option value="lop"    <?= $sortBy==='lop'    ? 'selected':'' ?>>Theo lớp</option>
      <option value="ho_ten" <?= $sortBy==='ho_ten' ? 'selected':'' ?>>Theo tên A→Z</option>
    </select>
    <button type="submit" class="btn-search"><i class="ti ti-search"></i> Tìm kiếm</button>
    <?php if ($search !== '' || $sortBy !== ''): ?>
      <a href="dashboard.php" class="btn-reset"><i class="ti ti-x"></i> Đặt lại</a>
    <?php endif; ?>
    <?php if ($tongHS > 0): ?>
      <a href="javascript:void(0)" onclick="confirmDeleteAll()" class="btn-delete-all">
        <i class="ti ti-trash"></i> Xóa tất cả
      </a>
    <?php endif; ?>
  </form>

  <?php if ($search !== '' || $sortBy !== ''): ?>
    <div class="search-info">
      <?php if ($search !== ''): ?>Tìm thấy <strong><?= $total ?></strong> kết quả cho "<strong><?= htmlspecialchars($search) ?></strong>" — <?php endif; ?>
      Sắp xếp: <strong><?= match($sortBy) { 'lop'=>'Theo lớp','ho_ten'=>'Theo tên A→Z',default=>'Mới nhất' } ?></strong>
    </div>
  <?php endif; ?>

  <h2>Danh sách học sinh đã đăng ký</h2>

  <!-- Bảng desktop -->
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th style="width:45px;">STT</th>
          <th>Họ tên</th>
          <th style="width:90px;">Ngày sinh</th>
          <th style="width:55px;">Lớp</th>
          <th style="width:105px;">SĐT</th>
          <th>Email</th>
          <th>Nguyện vọng 1</th>
          <th>Nguyện vọng 2</th>
          <th style="width:135px;">Ngày đăng ký</th>
          <th style="width:90px;text-align:center;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php $stt=$offset+1; while ($row=$listResult->fetch_assoc()): ?>
        <tr>
          <td style="text-align:center;"><?= $stt++ ?></td>
          <td><?= htmlspecialchars($row['ho_ten']) ?></td>
          <td style="font-size:12px;"><?= htmlspecialchars($row['ngay_sinh'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['lop']) ?></td>
          <td><?= htmlspecialchars($row['so_dien_thoai']) ?></td>
          <td style="font-size:12px;"><?= htmlspecialchars($row['email']) ?></td>
          <td style="font-size:12px;"><?= htmlspecialchars($row['nguyen_vong_1']) ?></td>
          <td style="font-size:12px;"><?= htmlspecialchars($row['nguyen_vong_2']) ?></td>
          <td style="font-size:12px;"><?= $row['ngay_dang_ky'] ?></td>
          <td style="text-align:center;white-space:nowrap;">
            <button class="btn-edit-row" onclick="openEdit(
              <?= $row['id'] ?>,
              '<?= addslashes(htmlspecialchars($row['ho_ten'])) ?>',
              '<?= addslashes($row['lop']) ?>',
              '<?= addslashes($row['so_dien_thoai']) ?>',
              '<?= addslashes($row['email']) ?>',
              '<?= addslashes(htmlspecialchars($row['nguyen_vong_1'])) ?>',
              '<?= addslashes(htmlspecialchars($row['nguyen_vong_2'])) ?>'
            )"><i class="ti ti-edit"></i></button>
            <a href="javascript:void(0)"
               onclick="confirmDelete(<?= $row['id'] ?>,'<?= addslashes(htmlspecialchars($row['ho_ten'])) ?>')"
               class="btn-del-row"><i class="ti ti-trash"></i></a>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total===0): ?>
          <tr><td colspan="10" style="text-align:center;color:#888;padding:20px;">Không tìm thấy kết quả.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Card mobile -->
  <div class="card-list">
    <?php $listResult->data_seek(0); $stt=$offset+1; while ($row=$listResult->fetch_assoc()): ?>
    <div class="card">
      <div class="card-header">
        <div class="card-stt"><?= $stt++ ?></div>
        <div class="card-name"><?= htmlspecialchars($row['ho_ten']) ?></div>
      </div>
      <div class="card-row"><span class="card-label">Ngày sinh</span><span><?= htmlspecialchars($row['ngay_sinh']??'') ?></span></div>
      <div class="card-row"><span class="card-label">Lớp</span><span><?= htmlspecialchars($row['lop']) ?></span></div>
      <div class="card-row"><span class="card-label">SĐT</span><span><?= htmlspecialchars($row['so_dien_thoai']) ?></span></div>
      <div class="card-row"><span class="card-label">Ngày ĐK</span><span style="font-size:12px;"><?= $row['ngay_dang_ky'] ?></span></div>
      <div class="card-nv">
        NV1: <strong><?= htmlspecialchars($row['nguyen_vong_1']) ?></strong><br>
        NV2: <strong><?= htmlspecialchars($row['nguyen_vong_2']) ?></strong>
      </div>
      <div class="card-actions">
        <button class="btn-edit-row" onclick="openEdit(
          <?= $row['id'] ?>,'<?= addslashes(htmlspecialchars($row['ho_ten'])) ?>',
          '<?= addslashes($row['lop']) ?>','<?= addslashes($row['so_dien_thoai']) ?>',
          '<?= addslashes($row['email']) ?>',
          '<?= addslashes(htmlspecialchars($row['nguyen_vong_1'])) ?>',
          '<?= addslashes(htmlspecialchars($row['nguyen_vong_2'])) ?>'
        )"><i class="ti ti-edit"></i> Sửa</button>
        <a href="javascript:void(0)"
           onclick="confirmDelete(<?= $row['id'] ?>,'<?= addslashes(htmlspecialchars($row['ho_ten'])) ?>')"
           class="btn-del-row"><i class="ti ti-trash"></i> Xóa</a>
      </div>
    </div>
    <?php endwhile; ?>
  </div>

  <!-- Phân trang -->
  <?php if ($totalPages > 1):
    $s = http_build_query(array_filter(['search'=>$search,'sort_by'=>$sortBy]));
    $s = $s ? '&'.$s : ''; ?>
    <div class="pagination">
      <?php if ($page>1): ?>
        <a href="?page=1<?= $s ?>">«</a>
        <a href="?page=<?= $page-1 ?><?= $s ?>">‹ Trước</a>
      <?php else: ?><span class="disabled">«</span><span class="disabled">‹ Trước</span><?php endif; ?>
      <?php for ($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
        <?php if ($p===$page): ?><span class="active"><?= $p ?></span>
        <?php else: ?><a href="?page=<?= $p ?><?= $s ?>"><?= $p ?></a><?php endif; ?>
      <?php endfor; ?>
      <?php if ($page<$totalPages): ?>
        <a href="?page=<?= $page+1 ?><?= $s ?>">Sau ›</a>
        <a href="?page=<?= $totalPages ?><?= $s ?>">»</a>
      <?php else: ?><span class="disabled">Sau ›</span><span class="disabled">»</span><?php endif; ?>
    </div>
    <div class="pagination-info">Trang <?= $page ?> / <?= $totalPages ?> &nbsp;|&nbsp; Tổng <?= $total ?> học sinh</div>
  <?php endif; ?>

</main>

<!-- Modal xác nhận xóa 1 học sinh -->
<div class="modal-overlay" id="confirmDeleteModal">
  <div class="modal confirm-modal">
    <div class="confirm-icon"><i class="ti ti-alert-triangle"></i></div>
    <div class="confirm-title">Xác nhận xoá</div>
    <div class="confirm-desc" id="confirm-desc">Bạn có chắc muốn xoá học sinh này không?</div>
    <div class="modal-footer" style="justify-content:center;gap:12px;">
      <button class="btn-cancel" onclick="closeConfirm()"><i class="ti ti-x"></i> Huỷ</button>
      <a href="#" id="confirm-delete-btn" class="btn-confirm-delete"><i class="ti ti-trash"></i> Xoá</a>
    </div>
  </div>
</div>

<!-- Modal xác nhận xóa tất cả -->
<div class="modal-overlay" id="confirmDeleteAllModal">
  <div class="modal confirm-modal">
    <div class="confirm-icon"><i class="ti ti-alert-triangle"></i></div>
    <div class="confirm-title">Xoá toàn bộ danh sách?</div>
    <div class="confirm-desc">Hành động này sẽ xoá <strong>tất cả <?= $tongHS ?> học sinh</strong> đã đăng ký. Không thể hoàn tác!</div>
    <div class="modal-footer" style="justify-content:center;gap:12px;">
      <button class="btn-cancel" onclick="closeConfirmAll()"><i class="ti ti-x"></i> Huỷ</button>
      <a href="?delete_all=1" class="btn-confirm-delete"><i class="ti ti-trash"></i> Xoá tất cả</a>
    </div>
  </div>
</div>

<!-- Modal sửa học sinh -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title"><i class="ti ti-edit"></i> Sửa thông tin học sinh</h3>
      <button class="modal-close" onclick="closeEdit()">×</button>
    </div>
    <form method="POST" onsubmit="return validateEdit()">
      <div id="edit-error"></div>
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="edit_id" id="edit_id">
      <div class="modal-row">
        <div><label>Họ và tên:</label><input type="text" name="edit_ho_ten" id="edit_ho_ten"></div>
        <div><label>Lớp:</label><input type="text" name="edit_lop" id="edit_lop"></div>
      </div>
      <div class="modal-row">
        <div><label>Số điện thoại:</label><input type="text" name="edit_sdt" id="edit_sdt" maxlength="10" placeholder="Ví dụ: 0901234567"></div>
        <div><label>Email:</label><input type="text" name="edit_email" id="edit_email" placeholder="Ví dụ: ten@gmail.com"></div>
      </div>
      <label>Nguyện vọng 1:</label>
      <select name="edit_nv1" id="edit_nv1">
        <?php foreach ($tohops as $th): ?>
          <option value="<?= htmlspecialchars($th) ?>"><?= htmlspecialchars($th) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Nguyện vọng 2:</label>
      <select name="edit_nv2" id="edit_nv2">
        <?php foreach ($tohops as $th): ?>
          <option value="<?= htmlspecialchars($th) ?>"><?= htmlspecialchars($th) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeEdit()"><i class="ti ti-x"></i> Huỷ</button>
        <button type="submit" class="btn-save"><i class="ti ti-device-floppy"></i> Lưu</button>
      </div>
    </form>
  </div>
</div>

<script>
<?php
$labelsJS    = json_encode(array_values($allTH));
$shortLabels = array_values(array_map(fn($th,$i)=>'TH'.($i+1),$allTH,array_keys($allTH)));
$nv1JS       = json_encode(array_values(array_map(fn($th)=>$statsNV1[$th]??0,$allTH)));
$nv2JS       = json_encode(array_values(array_map(fn($th)=>$statsNV2[$th]??0,$allTH)));
?>
const labels      = <?= $labelsJS ?>;
const shortLabels = <?= json_encode($shortLabels) ?>;
const nv1Data     = <?= $nv1JS ?>;
const nv2Data     = <?= $nv2JS ?>;
const ngayLabels  = <?= json_encode(array_values($ngayLabels)) ?>;
const ngayCounts  = <?= json_encode(array_values($ngayCounts)) ?>;

if (document.getElementById('nvChart')) {
  new Chart(document.getElementById('nvChart'), {
    type: 'bar',
    data: { labels: shortLabels, datasets: [
      { label:'NV1', data:nv1Data, backgroundColor:'#378ADD', borderRadius:4 },
      { label:'NV2', data:nv2Data, backgroundColor:'#1D9E75', borderRadius:4 }
    ]},
    options: { responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{display:false}, tooltip:{callbacks:{title:(items)=>labels[items[0].dataIndex]}} },
      scales:{ x:{grid:{display:false},ticks:{color:'#888'}}, y:{grid:{color:'rgba(0,0,0,0.05)'},ticks:{color:'#888',stepSize:1,precision:0}} }
    }
  });
}

if (document.getElementById('ngayChart')) {
  new Chart(document.getElementById('ngayChart'), {
    type: 'line',
    data: { labels: ngayLabels, datasets:[{
      label:'Số đăng ký', data:ngayCounts,
      borderColor:'#004080', backgroundColor:'rgba(0,64,128,0.08)',
      borderWidth:2, pointBackgroundColor:'#004080', pointRadius:4, fill:true, tension:0.3
    }]},
    options: { responsive:true, maintainAspectRatio:false,
      plugins:{legend:{display:false}},
      scales:{ x:{grid:{display:false},ticks:{color:'#888',font:{size:12}}}, y:{grid:{color:'rgba(0,0,0,0.05)'},ticks:{color:'#888',stepSize:1,precision:0},beginAtZero:true} }
    }
  });
}

// Confirm xóa 1 học sinh
function confirmDelete(id, ten) {
  document.getElementById('confirm-desc').innerHTML = `Bạn có chắc muốn xoá học sinh <strong>${ten}</strong> không?<br><small style="color:#dc3545;">Hành động này không thể hoàn tác!</small>`;
  document.getElementById('confirm-delete-btn').href = `?delete_id=${id}`;
  document.getElementById('confirmDeleteModal').classList.add('active');
}
function closeConfirm() {
  document.getElementById('confirmDeleteModal').classList.remove('active');
}

// Confirm xóa tất cả
function confirmDeleteAll() {
  document.getElementById('confirmDeleteAllModal').classList.add('active');
}
function closeConfirmAll() {
  document.getElementById('confirmDeleteAllModal').classList.remove('active');
}

// Đóng modal khi click ngoài hoặc ESC
document.getElementById('confirmDeleteModal').addEventListener('click', function(e){ if(e.target===this) closeConfirm(); });
document.getElementById('confirmDeleteAllModal').addEventListener('click', function(e){ if(e.target===this) closeConfirmAll(); });
document.getElementById('editModal').addEventListener('click', function(e){ if(e.target===this) closeEdit(); });
document.addEventListener('keydown', e=>{ if(e.key==='Escape'){closeEdit();closeConfirm();closeConfirmAll();} });

// Modal sửa
function openEdit(id, ten, lop, sdt, email, nv1, nv2) {
  document.getElementById('edit_id').value     = id;
  document.getElementById('edit_ho_ten').value = ten;
  document.getElementById('edit_lop').value    = lop;
  document.getElementById('edit_sdt').value    = sdt;
  document.getElementById('edit_email').value  = email;
  const s1 = document.getElementById('edit_nv1');
  const s2 = document.getElementById('edit_nv2');
  for (let o of s1.options) o.selected = (o.value === nv1);
  for (let o of s2.options) o.selected = (o.value === nv2);
  document.getElementById('edit-error').style.display = 'none';
  document.getElementById('editModal').classList.add('active');
  document.getElementById('edit_ho_ten').focus();
}
function closeEdit() {
  document.getElementById('editModal').classList.remove('active');
}

// Validate sửa
function validateEdit() {
  const errorBox = document.getElementById('edit-error');
  errorBox.style.display = 'none';
  const ten   = document.getElementById('edit_ho_ten').value.trim();
  const lop   = document.getElementById('edit_lop').value.trim();
  const sdt   = document.getElementById('edit_sdt').value.trim();
  const email = document.getElementById('edit_email').value.trim();
  const nv1   = document.getElementById('edit_nv1').value;
  const nv2   = document.getElementById('edit_nv2').value;
  if (!ten)   return showEditError('Vui lòng nhập họ và tên.');
  if (!lop)   return showEditError('Vui lòng nhập lớp.');
  if (!sdt)   return showEditError('Vui lòng nhập số điện thoại.');
  if (!/^(03|05|07|08|09)\d{8}$/.test(sdt)) return showEditError('Số điện thoại không hợp lệ. Phải là 10 số bắt đầu 03x, 05x, 07x, 08x hoặc 09x.');
  if (!email) return showEditError('Vui lòng nhập email.');
  if (!/^[^\s@]+@gmail\.com$/.test(email))   return showEditError('Email phải có đuôi @gmail.com.');
  if (!nv1)   return showEditError('Vui lòng chọn nguyện vọng 1.');
  if (!nv2)   return showEditError('Vui lòng chọn nguyện vọng 2.');
  if (nv1===nv2) return showEditError('Nguyện vọng 1 và 2 không được trùng nhau.');
  return true;
}
function showEditError(msg) {
  const errorBox = document.getElementById('edit-error');
  errorBox.textContent   = '⚠ ' + msg;
  errorBox.style.display = 'block';
  setTimeout(()=>{
    errorBox.style.transition='opacity 0.5s';
    errorBox.style.opacity='0';
    setTimeout(()=>{errorBox.style.display='none';errorBox.style.opacity='1';},500);
  },3000);
  return false;
}
</script>

</body>
</html>