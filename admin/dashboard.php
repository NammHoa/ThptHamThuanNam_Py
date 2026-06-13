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

//xóa
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM hoc_sinh WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['dash_msg'] = 'deleted';
    session_write_close();
    header("Location: dashboard.php");
    exit;
}
//sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id    = intval($_POST['edit_id']);
    $ten   = trim($_POST['edit_ho_ten']);
    $lop   = trim($_POST['edit_lop']);
    $sbd   = trim($_POST['edit_sbd']);
    $sdt   = trim($_POST['edit_sdt']);
    $email = trim($_POST['edit_email']);
    $nv1   = trim($_POST['edit_nv1']);
    $nv2   = trim($_POST['edit_nv2']);
    $stmt = $conn->prepare("UPDATE hoc_sinh SET ho_ten=?, lop=?, so_bao_danh=?, so_dien_thoai=?, email=?, nguyen_vong_1=?, nguyen_vong_2=? WHERE id=?");
    $stmt->bind_param("sssssssi", $ten, $lop, $sbd, $sdt, $email, $nv1, $nv2, $id);
    $stmt->execute();
    $_SESSION['dash_msg'] = 'edited';
    session_write_close();
    header("Location: dashboard.php");
    exit;
}

// Thông báo session
if (isset($_SESSION['dash_msg'])) {
    $m = $_SESSION['dash_msg'];
    unset($_SESSION['dash_msg']);
    if ($m === 'deleted') { $resultMsg = "✅ Đã xoá học sinh thành công.";       $resultType = 'success'; }
    if ($m === 'edited')  { $resultMsg = "✅ Đã cập nhật thông tin thành công."; $resultType = 'success'; }
}

$tongHS   = $conn->query("SELECT COUNT(*) FROM hoc_sinh")->fetch_row()[0];
$homNay   = $conn->query("SELECT COUNT(*) FROM hoc_sinh WHERE DATE(ngay_dang_ky) = CURDATE()")->fetch_row()[0];
$tongTT   = $conn->query("SELECT COUNT(*) FROM danh_sach_trung_tuyen")->fetch_row()[0];
$chuaDK   = max(0, $tongTT - $tongHS);

$statsNV1 = [];
$statsNV2 = [];
$r = $conn->query("SELECT nguyen_vong_1, COUNT(*) as cnt FROM hoc_sinh GROUP BY nguyen_vong_1 ORDER BY cnt DESC");
while ($row = $r->fetch_assoc()) $statsNV1[$row['nguyen_vong_1']] = $row['cnt'];
$r = $conn->query("SELECT nguyen_vong_2, COUNT(*) as cnt FROM hoc_sinh GROUP BY nguyen_vong_2 ORDER BY cnt DESC");
while ($row = $r->fetch_assoc()) $statsNV2[$row['nguyen_vong_2']] = $row['cnt'];
$allTH = array_unique(array_merge(array_keys($statsNV1), array_keys($statsNV2)));

// Tìm kiếm + phân trang
$search = trim($_GET['search'] ?? '');
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

if ($search !== '') {
    $like = "%$search%";
    $r = $conn->prepare("SELECT COUNT(*) FROM hoc_sinh WHERE ho_ten LIKE ? OR so_bao_danh LIKE ? OR lop LIKE ?");
    $r->bind_param("sss", $like, $like, $like); $r->execute(); $r->bind_result($total); $r->fetch(); $r->close();
    $list = $conn->prepare("SELECT * FROM hoc_sinh WHERE ho_ten LIKE ? OR so_bao_danh LIKE ? OR lop LIKE ? ORDER BY ngay_dang_ky DESC LIMIT ? OFFSET ?");
    $list->bind_param("sssii", $like, $like, $like, $limit, $offset);
} else {
    $r = $conn->query("SELECT COUNT(*) FROM hoc_sinh");
    $total = $r->fetch_row()[0];
    $list = $conn->prepare("SELECT * FROM hoc_sinh ORDER BY ngay_dang_ky DESC LIMIT ? OFFSET ?");
    $list->bind_param("ii", $limit, $offset);
}
$list->execute();
$listResult = $list->get_result();
$totalPages = ceil($total / $limit);


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
  <link rel="stylesheet" href="admin_style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
  <style>
    .metric-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px; margin-bottom:20px; }
    .metric-card { background:#fff; border-radius:10px; padding:15px 18px; box-shadow:0 2px 8px rgba(0,0,0,0.07); }
    .metric-card .label { font-size:13px; color:#888; margin:0 0 5px; }
    .metric-card .value { font-size:26px; font-weight:500; color:#004080; margin:0; }
    .metric-card .sub   { font-size:12px; color:#aaa; margin:4px 0 0; }

    .stat-section { background:#fff; border-radius:10px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.07); }
    .stat-section h3 { color:#004080; margin:0 0 15px; font-size:16px; }

    .search-bar { display:flex; gap:10px; align-items:center; background:#fff; border-radius:10px; padding:14px 18px; margin-bottom:15px; box-shadow:0 2px 8px rgba(0,0,0,0.07); flex-wrap:wrap; }
    .search-bar input { flex:1; min-width:200px; padding:9px 14px; border:1px solid #ccc; border-radius:8px; font-size:14px; }
    .search-bar input:focus { border-color:#004080; outline:none; }
    .btn-search { padding:9px 20px; background:#004080; color:#fff; border:none; border-radius:8px; font-size:14px; cursor:pointer; }
    .btn-search:hover { background:#003060; }
    .btn-reset { padding:9px 14px; background:#e0e0e0; color:#555; border:none; border-radius:8px; font-size:14px; cursor:pointer; text-decoration:none; }
    .btn-reset:hover { background:#ccc; text-decoration:none; }

    .result-box { padding:12px 16px; border-radius:8px; margin-bottom:15px; font-size:14px; }
    .result-success { background:#d4edda; color:#155724; border-left:4px solid #28a745; }

    .table-wrapper { background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.07); overflow-x:auto; }
    table { width:100%; border-collapse:collapse; min-width:750px; }
    thead th { background:linear-gradient(135deg,#003366,#004080); color:#fff; padding:11px 10px; text-align:left; font-size:13px; }
    tbody td { padding:10px; border-bottom:1px solid #f0f0f0; font-size:13px; vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#f8f9ff; }

    .btn-edit-row { padding:4px 9px; background:transparent; color:#007bff; border:1px solid #007bff; border-radius:5px; font-size:11px; cursor:pointer; margin-right:3px; transition:all 0.2s; }
    .btn-edit-row:hover { background:#007bff; color:#fff; }
    .btn-del-row  { padding:4px 9px; background:transparent; color:#dc3545; border:1px solid #dc3545; border-radius:5px; font-size:11px; cursor:pointer; transition:all 0.2s; }
    .btn-del-row:hover  { background:#dc3545; color:#fff; }

    .search-info { font-size:13px; color:#888; margin-bottom:10px; }

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
    .modal-title { font-size:16px; font-weight:bold; color:#004080; margin:0; }
    .modal-close { background:none; border:none; font-size:22px; cursor:pointer; color:#888; }
    .modal label { display:block; font-weight:bold; color:#444; font-size:13px; margin:10px 0 4px; }
    .modal input, .modal select { width:100%; padding:8px 12px; border:1px solid #ccc; border-radius:7px; font-size:14px; box-sizing:border-box; }
    .modal input:focus, .modal select:focus { border-color:#004080; outline:none; }
    .modal-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .modal-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:15px; }
    .btn-cancel { padding:8px 16px; background:#e0e0e0; color:#555; border:none; border-radius:7px; font-size:13px; cursor:pointer; }
    .btn-save   { padding:8px 20px; background:linear-gradient(135deg,#28a745,#20c050); color:#fff; border:none; border-radius:7px; font-size:13px; font-weight:bold; cursor:pointer; }

    .card-list { display:none; }
    .card { background:#fff; border-radius:10px; padding:14px; margin-bottom:10px; box-shadow:0 2px 6px rgba(0,0,0,0.07); }
    .card-header { display:flex; align-items:center; gap:10px; margin-bottom:8px; padding-bottom:8px; border-bottom:1px solid #f0f0f0; }
    .card-stt { background:#004080; color:#fff; border-radius:50%; width:30px; height:30px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold; flex-shrink:0; }
    .card-name { font-weight:bold; color:#004080; font-size:15px; flex:1; }
    .card-row { display:flex; justify-content:space-between; padding:4px 0; font-size:13px; border-bottom:1px solid #f8f8f8; }
    .card-label { color:#888; }
    .card-nv { margin-top:8px; background:#f0f4f8; border-radius:6px; padding:8px 12px; font-size:12px; line-height:1.8; }
    .card-actions { display:flex; gap:8px; margin-top:10px; }
    .card-actions .btn-edit-row, .card-actions .btn-del-row { flex:1; text-align:center; padding:7px; font-size:12px; }

    @media (max-width:600px) {
      .table-wrapper { display:none; }
      .card-list { display:block; }
      .metric-grid { grid-template-columns:1fr 1fr; }
      .modal-row { grid-template-columns:1fr; }
    }
  </style>
</head>
<body>

<header>
  <h1>Admin Dashboard</h1>
  <div class="btn-group">
    <a href="import_trungtuyen.php" class="button">📥 Tải lên trúng tuyển Excel</a>
    <a href="download.php" class="button">📤 Tải danh sách đăng kí</a>
    <a href="deadline.php" class="button">⚙️ Cài đặt hạn đăng ký</a>
    <a href="tohop.php" class="button">📚 Cập nhật tổ hợp</a>
    <a href="logout.php" class="button danger">🚪 Đăng xuất</a>
  </div>
</header>

<main>

  <?php if ($resultMsg): ?>
    <div class="result-box result-<?= $resultType ?>" id="result-msg"><?= $resultMsg ?></div>
    <script>
      setTimeout(() => {
        const m = document.getElementById('result-msg');
        if (m) { m.style.transition='opacity 0.5s'; m.style.opacity='0'; setTimeout(()=>m.style.display='none',500); }
      }, 3000);
    </script>
  <?php endif; ?>

  <!-- THỐNG KÊ NHANH -->
  <div class="metric-grid">
    <div class="metric-card">
      <p class="label">Tổng đăng ký</p>
      <p class="value"><?= $tongHS ?></p>
      <p class="sub">học sinh</p>
    </div>
    <div class="metric-card">
      <p class="label">Hôm nay</p>
      <p class="value"><?= $homNay ?></p>
      <p class="sub">đăng ký mới</p>
    </div>
    <div class="metric-card">
      <p class="label">Trúng tuyển</p>
      <p class="value"><?= $tongTT ?></p>
      <p class="sub">trong danh sách</p>
    </div>
    <div class="metric-card">
      <p class="label">Chưa đăng ký</p>
      <p class="value"><?= $chuaDK ?></p>
      <p class="sub">học sinh còn lại</p>
    </div>
  </div>

  <?php if (!empty($allTH)): ?>
  <div class="stat-section">
    <h3>📊 Thống kê đăng ký theo tổ hợp</h3>

    <div style="display:flex; flex-wrap:wrap; gap:16px; margin-bottom:10px; font-size:12px; color:#888;">
      <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:10px;border-radius:2px;background:#378ADD;"></span>Nguyện vọng 1</span>
      <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:10px;border-radius:2px;background:#1D9E75;"></span>Nguyện vọng 2</span>
    </div>

    <div style="position:relative; width:100%; height:260px; margin-bottom:20px;">
      <canvas id="nvChart" role="img" aria-label="Biểu đồ thống kê nguyện vọng theo tổ hợp">Thống kê nguyện vọng.</canvas>
    </div>

    <table class="stat-table">
      <thead>
        <tr>
          <th>Tổ hợp môn</th>
          <th style="text-align:center;">NV1</th>
          <th style="text-align:center;">NV2</th>
          <th style="text-align:center;">Tổng</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allTH as $th): ?>
          <?php
            $n1 = $statsNV1[$th] ?? 0;
            $n2 = $statsNV2[$th] ?? 0;
          ?>
          <tr>
            <td><?= htmlspecialchars($th) ?></td>
            <td style="text-align:center;"><span class="badge-nv1"><?= $n1 ?></span></td>
            <td style="text-align:center;"><span class="badge-nv2"><?= $n2 ?></span></td>
            <td style="text-align:center; font-weight:500; color:#004080;"><?= $n1 + $n2 ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <form method="GET" class="search-bar">
    <input type="text" name="search"
           placeholder="🔍 Tìm theo họ tên, số báo danh hoặc lớp..."
           value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn-search">Tìm kiếm</button>
    <?php if ($search !== ''): ?>
      <a href="dashboard.php" class="btn-reset">✖ Xóa bộ lọc</a>
    <?php endif; ?>
  </form>

  <?php if ($search !== ''): ?>
    <div class="search-info">Tìm thấy <strong><?= $total ?></strong> kết quả cho "<strong><?= htmlspecialchars($search) ?></strong>"</div>
  <?php endif; ?>

  <h2>Danh sách học sinh đã đăng ký</h2>

  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th style="width:45px;">STT</th>
          <th>Họ tên</th>
          <th style="width:55px;">Lớp</th>
          <th style="width:90px;">Số báo danh</th>
          <th style="width:105px;">SĐT</th>
          <th>Email</th>
          <th>Nguyện vọng 1</th>
          <th>Nguyện vọng 2</th>
          <th style="width:135px;">Ngày đăng ký</th>
          <th style="width:90px; text-align:center;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php $stt = $offset+1; while ($row = $listResult->fetch_assoc()): ?>
          <tr>
            <td style="text-align:center;"><?= $stt++ ?></td>
            <td><?= htmlspecialchars($row['ho_ten']) ?></td>
            <td><?= htmlspecialchars($row['lop']) ?></td>
            <td><?= htmlspecialchars($row['so_bao_danh']) ?></td>
            <td><?= htmlspecialchars($row['so_dien_thoai']) ?></td>
            <td style="font-size:12px;"><?= htmlspecialchars($row['email']) ?></td>
            <td style="font-size:12px;"><?= htmlspecialchars($row['nguyen_vong_1']) ?></td>
            <td style="font-size:12px;"><?= htmlspecialchars($row['nguyen_vong_2']) ?></td>
            <td style="font-size:12px;"><?= $row['ngay_dang_ky'] ?></td>
            <td style="text-align:center; white-space:nowrap;">
              <button class="btn-edit-row" onclick="openEdit(
                <?= $row['id'] ?>,
                '<?= addslashes(htmlspecialchars($row['ho_ten'])) ?>',
                '<?= addslashes($row['lop']) ?>',
                '<?= addslashes($row['so_bao_danh']) ?>',
                '<?= addslashes($row['so_dien_thoai']) ?>',
                '<?= addslashes($row['email']) ?>',
                '<?= addslashes(htmlspecialchars($row['nguyen_vong_1'])) ?>',
                '<?= addslashes(htmlspecialchars($row['nguyen_vong_2'])) ?>'
              )">✏️</button>
              <a href="?delete_id=<?= $row['id'] ?><?= $search ? '&search='.urlencode($search) : '' ?>"
                 class="btn-del-row"
                 onclick="return confirm('Xác nhận xoá học sinh <?= addslashes(htmlspecialchars($row['ho_ten'])) ?>?')">🗑️</a>
            </td>
          </tr>
        <?php endwhile; ?>
        <?php if ($total === 0): ?>
          <tr><td colspan="10" style="text-align:center; color:#888; padding:20px;">Không tìm thấy kết quả.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card-list">
    <?php $listResult->data_seek(0); $stt = $offset+1; while ($row = $listResult->fetch_assoc()): ?>
      <div class="card">
        <div class="card-header">
          <div class="card-stt"><?= $stt++ ?></div>
          <div class="card-name"><?= htmlspecialchars($row['ho_ten']) ?></div>
        </div>
        <div class="card-row"><span class="card-label">Lớp</span><span><?= htmlspecialchars($row['lop']) ?></span></div>
        <div class="card-row"><span class="card-label">SBD</span><span><?= htmlspecialchars($row['so_bao_danh']) ?></span></div>
        <div class="card-row"><span class="card-label">SĐT</span><span><?= htmlspecialchars($row['so_dien_thoai']) ?></span></div>
        <div class="card-row"><span class="card-label">Ngày ĐK</span><span style="font-size:12px;"><?= $row['ngay_dang_ky'] ?></span></div>
        <div class="card-nv">
          NV1: <strong><?= htmlspecialchars($row['nguyen_vong_1']) ?></strong><br>
          NV2: <strong><?= htmlspecialchars($row['nguyen_vong_2']) ?></strong>
        </div>
        <div class="card-actions">
          <button class="btn-edit-row" onclick="openEdit(
            <?= $row['id'] ?>,'<?= addslashes(htmlspecialchars($row['ho_ten'])) ?>',
            '<?= addslashes($row['lop']) ?>','<?= addslashes($row['so_bao_danh']) ?>',
            '<?= addslashes($row['so_dien_thoai']) ?>','<?= addslashes($row['email']) ?>',
            '<?= addslashes(htmlspecialchars($row['nguyen_vong_1'])) ?>',
            '<?= addslashes(htmlspecialchars($row['nguyen_vong_2'])) ?>'
          )">✏️ Sửa</button>
          <a href="?delete_id=<?= $row['id'] ?>" class="btn-del-row"
             onclick="return confirm('Xác nhận xoá?')">🗑️ Xóa</a>
        </div>
      </div>
    <?php endwhile; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <?php $s = $search ? '&search='.urlencode($search) : ''; ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=1<?= $s ?>" title="Trang đầu">«</a>
        <a href="?page=<?= $page-1 ?><?= $s ?>">‹ Trước</a>
      <?php else: ?>
        <span class="disabled">«</span>
        <span class="disabled">‹ Trước</span>
      <?php endif; ?>
      <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
        <?php if ($p === $page): ?>
          <span class="active"><?= $p ?></span>
        <?php else: ?>
          <a href="?page=<?= $p ?><?= $s ?>"><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page+1 ?><?= $s ?>">Sau ›</a>
        <a href="?page=<?= $totalPages ?><?= $s ?>" title="Trang cuối">»</a>
      <?php else: ?>
        <span class="disabled">Sau ›</span>
        <span class="disabled">»</span>
      <?php endif; ?>
    </div>
    <div class="pagination-info">Trang <?= $page ?> / <?= $totalPages ?> &nbsp;|&nbsp; Tổng <?= $total ?> học sinh</div>
  <?php endif; ?>

</main>

<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">✏️ Sửa thông tin học sinh</h3>
      <button class="modal-close" onclick="closeEdit()">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="edit_id" id="edit_id">
      <div class="modal-row">
        <div>
          <label>Họ và tên:</label>
          <input type="text" name="edit_ho_ten" id="edit_ho_ten" required>
        </div>
        <div>
          <label>Lớp:</label>
          <input type="text" name="edit_lop" id="edit_lop">
        </div>
      </div>
      <div class="modal-row">
        <div>
          <label>Số báo danh:</label>
          <input type="text" name="edit_sbd" id="edit_sbd" required>
        </div>
        <div>
          <label>Số điện thoại:</label>
          <input type="text" name="edit_sdt" id="edit_sdt">
        </div>
      </div>
      <label>Email:</label>
      <input type="email" name="edit_email" id="edit_email">
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
        <button type="button" class="btn-cancel" onclick="closeEdit()">Huỷ</button>
        <button type="submit" class="btn-save">💾 Lưu</button>
      </div>
    </form>
  </div>
</div>

<script>
<?php
$labelsJS = json_encode(array_values($allTH));
$nv1JS    = json_encode(array_values(array_map(fn($th) => $statsNV1[$th] ?? 0, $allTH)));
$nv2JS    = json_encode(array_values(array_map(fn($th) => $statsNV2[$th] ?? 0, $allTH)));
?>
const labels = <?= $labelsJS ?>;
const shortLabels = labels.map((l, i) => 'TH' + (i+1));
const nv1Data = <?= $nv1JS ?>;
const nv2Data = <?= $nv2JS ?>;

if (document.getElementById('nvChart')) {
  new Chart(document.getElementById('nvChart'), {
    type: 'bar',
    data: {
      labels: shortLabels,
      datasets: [
        { label: 'NV1', data: nv1Data, backgroundColor: '#378ADD', borderRadius: 4 },
        { label: 'NV2', data: nv2Data, backgroundColor: '#1D9E75', borderRadius: 4 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false },
        tooltip: { callbacks: { title: (items) => labels[items[0].dataIndex] } }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#888' } },
        y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#888' } }
      }
    }
  });
}

function openEdit(id, ten, lop, sbd, sdt, email, nv1, nv2) {
  document.getElementById('edit_id').value    = id;
  document.getElementById('edit_ho_ten').value = ten;
  document.getElementById('edit_lop').value    = lop;
  document.getElementById('edit_sbd').value    = sbd;
  document.getElementById('edit_sdt').value    = sdt;
  document.getElementById('edit_email').value  = email;
  const s1 = document.getElementById('edit_nv1');
  const s2 = document.getElementById('edit_nv2');
  for (let o of s1.options) o.selected = (o.value === nv1);
  for (let o of s2.options) o.selected = (o.value === nv2);
  document.getElementById('editModal').classList.add('active');
  document.getElementById('edit_ho_ten').focus();
}
function closeEdit() {
  document.getElementById('editModal').classList.remove('active');
}
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeEdit();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEdit(); });
</script>
</body>
</html>