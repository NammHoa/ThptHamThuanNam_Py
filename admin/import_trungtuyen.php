<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Lỗi kết nối CSDL: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$resultMsg = '';
$resultType = '';

if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM danh_sach_trung_tuyen WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['import_msg'] = 'deleted';
    $search = $_GET['search'] ?? '';
    session_write_close();
    header("Location: import_trungtuyen.php" . ($search ? "?search=".urlencode($search) : ""));
    exit;
}

if (isset($_GET['clear'])) {
    $conn->query("DELETE FROM danh_sach_trung_tuyen");
    $_SESSION['import_msg'] = 'cleared';
    session_write_close();
    header("Location: import_trungtuyen.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id        = intval($_POST['edit_id']);
    $ho_ten    = trim($_POST['edit_ho_ten']);
    $sbd       = trim($_POST['edit_sbd']);
    $ngay_raw  = trim($_POST['edit_ngay_sinh']);
      if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngay_raw)) {
          $parts = explode('-', $ngay_raw);
          $ngay_sinh = $parts[2] . '/' . $parts[1] . '/' . $parts[0];
      } else {
          $ngay_sinh = $ngay_raw;
      }
    $stmt = $conn->prepare("UPDATE danh_sach_trung_tuyen SET ho_ten=?, so_bao_danh=?, ngay_sinh=? WHERE id=?");
    $stmt->bind_param("sssi", $ho_ten, $sbd, $ngay_sinh, $id);
    $stmt->execute();
    $_SESSION['import_msg'] = 'edited';
    session_write_close();
    header("Location: import_trungtuyen.php");
    exit;
}

if (isset($_SESSION['import_msg'])) {
    $msg = $_SESSION['import_msg'];
    unset($_SESSION['import_msg']);
    if ($msg === 'cleared')      { $resultMsg = "🗑️ Đã xoá toàn bộ danh sách.";            $resultType = 'danger';  }
    elseif ($msg === 'deleted')  { $resultMsg = "✅ Đã xoá học sinh thành công.";            $resultType = 'success'; }
    elseif ($msg === 'edited')   { $resultMsg = "✅ Đã cập nhật thông tin thành công.";      $resultType = 'success'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    $spreadsheet = IOFactory::load($_FILES['file_excel']['tmp_name']);
    $sheetData   = $spreadsheet->getActiveSheet()->toArray();
    $count = 0;
    for ($i = 3; $i < count($sheetData); $i++) {
        $sbd       = trim($sheetData[$i][1] ?? '');
        $name      = trim($sheetData[$i][2] ?? '');
        $ngay_sinh = trim($sheetData[$i][3] ?? '');
        if ($sbd === '' || $name === '') continue;
        $stmt = $conn->prepare("INSERT IGNORE INTO danh_sach_trung_tuyen (so_bao_danh, ho_ten, ngay_sinh) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $sbd, $name, $ngay_sinh);
        if ($stmt->execute()) $count++;
        $stmt->close();
    }
    $resultType = 'success';
    $resultMsg  = "✅ Đã import thành công <strong>$count</strong> dòng.";
}

$search = trim($_GET['search'] ?? '');
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

if ($search !== '') {
    $like = "%$search%";
    $r = $conn->prepare("SELECT COUNT(*) FROM danh_sach_trung_tuyen WHERE ho_ten LIKE ? OR so_bao_danh LIKE ?");
    $r->bind_param("ss", $like, $like); $r->execute(); $r->bind_result($total); $r->fetch(); $r->close();
    $list = $conn->prepare("SELECT * FROM danh_sach_trung_tuyen WHERE ho_ten LIKE ? OR so_bao_danh LIKE ? ORDER BY id ASC LIMIT ? OFFSET ?");
    $list->bind_param("ssii", $like, $like, $limit, $offset);
} else {
    $r = $conn->query("SELECT COUNT(*) FROM danh_sach_trung_tuyen");
    $total = $r->fetch_row()[0];
    $list = $conn->prepare("SELECT * FROM danh_sach_trung_tuyen ORDER BY id ASC LIMIT ? OFFSET ?");
    $list->bind_param("ii", $limit, $offset);
}
$list->execute();
$listResult = $list->get_result();
$totalPages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý danh sách trúng tuyển</title>
  <link rel="stylesheet" href="admin_style.css">
  <style>
    .stats-bar { display:flex; align-items:center; justify-content:space-between; background:#fff; border-radius:10px; padding:15px 20px; margin-bottom:15px; box-shadow:0 2px 8px rgba(0,0,0,0.08); flex-wrap:wrap; gap:10px; }
    .stats-bar .stat { font-size:15px; color:#444; }
    .stats-bar .stat strong { font-size:22px; color:#004080; margin-right:5px; }
    .btn-danger-outline { padding:8px 16px; background:transparent; color:#dc3545; border:1.5px solid #dc3545; border-radius:6px; font-size:13px; cursor:pointer; text-decoration:none; transition:all 0.2s; white-space:nowrap; }
    .btn-danger-outline:hover { background:#dc3545; color:#fff; text-decoration:none; }
    .upload-section { background:#fff; border-radius:10px; padding:20px 25px; margin-bottom:15px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
    .upload-section h3 { color:#004080; margin:0 0 5px 0; font-size:16px; }
    .upload-section p { color:#888; font-size:13px; margin:0 0 12px 0; }
    .upload-area { border:2px dashed #b0c4de; border-radius:8px; padding:15px; text-align:center; background:#f8faff; margin-bottom:12px; }
    .upload-area input[type="file"] { font-size:14px; }
    .btn-upload { padding:9px 24px; background:linear-gradient(135deg,#28a745,#20c050); color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:bold; cursor:pointer; }
    .btn-upload:hover { background:linear-gradient(135deg,#218838,#1aab42); }
    .result-box { padding:14px 18px; border-radius:8px; margin-bottom:15px; font-size:14px; line-height:1.7; }
    .result-success { background:#d4edda; color:#155724; border-left:4px solid #28a745; }
    .result-warning { background:#fff3cd; color:#856404; border-left:4px solid #ffc107; }
    .result-danger  { background:#f8d7da; color:#721c24; border-left:4px solid #dc3545; }
    .search-bar { display:flex; gap:10px; align-items:center; background:#fff; border-radius:10px; padding:15px 20px; margin-bottom:15px; box-shadow:0 2px 8px rgba(0,0,0,0.08); flex-wrap:wrap; }
    .search-bar input { flex:1; min-width:200px; padding:9px 14px; border:1px solid #ccc; border-radius:8px; font-size:14px; }
    .search-bar input:focus { border-color:#004080; outline:none; }
    .btn-search { padding:9px 20px; background:#004080; color:#fff; border:none; border-radius:8px; font-size:14px; cursor:pointer; }
    .btn-search:hover { background:#003060; }
    .btn-reset { padding:9px 16px; background:#e0e0e0; color:#555; border:none; border-radius:8px; font-size:14px; cursor:pointer; text-decoration:none; }
    .btn-reset:hover { background:#ccc; text-decoration:none; }
    .search-result-info { font-size:13px; color:#888; margin-bottom:10px; padding:0 5px; }
    .list-table-wrapper { background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
    .list-table-wrapper table { min-width:550px; }
    .btn-edit-row { padding:5px 10px; background:transparent; color:#007bff; border:1px solid #007bff; border-radius:5px; font-size:12px; cursor:pointer; transition:all 0.2s; white-space:nowrap; margin-right:4px; }
    .btn-edit-row:hover { background:#007bff; color:#fff; }
    .btn-del-row { padding:5px 10px; background:transparent; color:#dc3545; border:1px solid #dc3545; border-radius:5px; font-size:12px; cursor:pointer; transition:all 0.2s; white-space:nowrap; }
    .btn-del-row:hover { background:#dc3545; color:#fff; }
    .pagination { display:flex; justify-content:center; gap:6px; padding:15px 0; flex-wrap:wrap; }
    .pagination a, .pagination span { padding:7px 13px; border-radius:6px; font-size:14px; text-decoration:none; border:1px solid #ddd; color:#004080; transition:all 0.2s; }
    .pagination a:hover { background:#004080; color:#fff; border-color:#004080; }
    .pagination .active { background:#004080; color:#fff; border-color:#004080; font-weight:bold; }
    .pagination .disabled { color:#ccc; cursor:default; }
    .pagination-info { text-align:center; font-size:13px; color:#888; margin-top:-8px; margin-bottom:10px; }
    .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; padding:15px; }
    .modal-overlay.active { display:flex; }
    .modal { background:#fff; border-radius:12px; padding:25px; width:100%; max-width:450px; box-shadow:0 10px 40px rgba(0,0,0,0.2); animation:slideIn 0.2s ease; }
    @keyframes slideIn { from{transform:translateY(-20px);opacity:0} to{transform:translateY(0);opacity:1} }
    .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; padding-bottom:12px; border-bottom:2px solid #f0f4f8; }
    .modal-title { font-size:17px; font-weight:bold; color:#004080; margin:0; }
    .modal-close { background:none; border:none; font-size:22px; cursor:pointer; color:#888; }
    .modal-close:hover { color:#333; }
    .modal label { display:block; font-weight:bold; color:#444; font-size:13px; margin-bottom:5px; margin-top:12px; }
    .modal input { width:100%; padding:9px 12px; border:1px solid #ccc; border-radius:7px; font-size:14px; box-sizing:border-box; }
    .modal input:focus { border-color:#004080; outline:none; }
    .modal-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:18px; }
    .btn-cancel { padding:9px 18px; background:#e0e0e0; color:#555; border:none; border-radius:7px; font-size:14px; cursor:pointer; }
    .btn-save { padding:9px 22px; background:linear-gradient(135deg,#28a745,#20c050); color:#fff; border:none; border-radius:7px; font-size:14px; font-weight:bold; cursor:pointer; }
    .list-card-list { display:none; }
    .list-card { background:#fff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.08); padding:12px 15px; margin-bottom:10px; }
    .list-card-header { display:flex; align-items:center; gap:10px; margin-bottom:8px; padding-bottom:8px; border-bottom:1px solid #f0f0f0; }
    .list-card-stt { background:#004080; color:#fff; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold; flex-shrink:0; }
    .list-card-name { font-weight:bold; color:#004080; font-size:14px; flex:1; }
    .list-card-info { font-size:13px; color:#666; margin-bottom:8px; }
    .list-card-actions { display:flex; gap:8px; }
    .list-card-actions .btn-edit-row, .list-card-actions .btn-del-row { flex:1; text-align:center; padding:7px; }
    @media (max-width:600px) {
      .list-table-wrapper { display:none; }
      .list-card-list { display:block; }
      .stats-bar { flex-direction:column; align-items:flex-start; }
      .modal-footer { flex-direction:column-reverse; }
      .btn-cancel, .btn-save { width:100%; text-align:center; }
    }
  </style>
</head>
<body>
<header>
  <h1>Quản lý danh sách trúng tuyển</h1>
  <div class="btn-group">
    <a href="dashboard.php" class="button">🏠 Dashboard</a>
    <a href="logout.php" class="button danger">🚪 Logout</a>
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

  <div class="stats-bar">
    <div class="stat"><strong><?= $total ?></strong> học sinh trong danh sách trúng tuyển</div>
    <?php if ($total > 0): ?>
      <a href="?clear=1" class="btn-danger-outline" onclick="return confirm('Xác nhận xoá TOÀN BỘ danh sách?')">🗑️ Xoá toàn bộ</a>
    <?php endif; ?>
  </div>

  <div class="upload-section">
    <h3>📥 Upload file Excel (.xlsx)</h3>
    <p>Cột B = Số báo danh | Cột C = Họ và tên | Cột D = Ngày sinh (hàng 1+2 là tiêu đề, dữ liệu từ hàng 3)</p>
    <form method="POST" enctype="multipart/form-data">
      <div class="upload-area">
        <input type="file" name="file_excel" accept=".xlsx" required>
      </div>
      <button type="submit" class="btn-upload">📤 Import dữ liệu</button>
    </form>
  </div>

  <?php if ($total > 0 || $search !== ''): ?>

    <!-- Tìm kiếm -->
    <form method="GET" class="search-bar">
      <input type="text" name="search"
             placeholder="🔍 Tìm theo tên hoặc số báo danh..."
             value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn-search">Tìm kiếm</button>
      <?php if ($search !== ''): ?>
        <a href="import_trungtuyen.php" class="btn-reset">✖ Xóa bộ lọc</a>
      <?php endif; ?>
    </form>

    <?php if ($search !== ''): ?>
      <div class="search-result-info">
        Tìm thấy <strong><?= $total ?></strong> kết quả cho "<strong><?= htmlspecialchars($search) ?></strong>"
      </div>
    <?php endif; ?>

    <h2>Danh sách hiện tại</h2>

    <div class="list-table-wrapper">
      <table>
        <thead>
          <tr>
            <th style="width:55px; text-align:center;">STT</th>
            <th>Họ và tên</th>
            <th style="width:120px;">Số báo danh</th>
            <th style="width:110px;">Ngày sinh</th>
            <th style="width:110px; text-align:center;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php $stt = $offset+1; while ($row = $listResult->fetch_assoc()): ?>
            <tr>
              <td style="text-align:center;"><?= $stt++ ?></td>
              <td><?= htmlspecialchars($row['ho_ten']) ?></td>
              <td><?= htmlspecialchars($row['so_bao_danh']) ?></td>
              <td><?= htmlspecialchars($row['ngay_sinh']) ?></td>
              <td style="text-align:center;">
                <button class="btn-edit-row"
                  onclick="openEdit(<?= $row['id'] ?>,'<?= addslashes(htmlspecialchars($row['ho_ten'])) ?>','<?= addslashes($row['so_bao_danh']) ?>','<?= addslashes($row['ngay_sinh']) ?>')">
                  ✏️
                </button>
                <a href="?delete_id=<?= $row['id'] ?><?= $search ? '&search='.urlencode($search) : '' ?>"
                   class="btn-del-row"
                   onclick="return confirm('Xác nhận xoá học sinh này?')">🗑️</a>
              </td>
            </tr>
          <?php endwhile; ?>
          <?php if ($total === 0): ?>
            <tr><td colspan="5" style="text-align:center; color:#888; padding:20px;">Không tìm thấy kết quả.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="list-card-list">
      <?php $listResult->data_seek(0); $stt = $offset+1; while ($row = $listResult->fetch_assoc()): ?>
        <div class="list-card">
          <div class="list-card-header">
            <div class="list-card-stt"><?= $stt++ ?></div>
            <div class="list-card-name"><?= htmlspecialchars($row['ho_ten']) ?></div>
          </div>
          <div class="list-card-info">
            SBD: <strong><?= htmlspecialchars($row['so_bao_danh']) ?></strong> &nbsp;|&nbsp;
            🎂 <?= htmlspecialchars($row['ngay_sinh']) ?>
          </div>
          <div class="list-card-actions">
            <button class="btn-edit-row"
              onclick="openEdit(<?= $row['id'] ?>,'<?= addslashes(htmlspecialchars($row['ho_ten'])) ?>','<?= addslashes($row['so_bao_danh']) ?>','<?= addslashes($row['ngay_sinh']) ?>')">
              ✏️ Sửa
            </button>
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
      <div class="pagination-info">
        Trang <?= $page ?> / <?= $totalPages ?> &nbsp;|&nbsp; Tổng <?= $total ?> học sinh
      </div>
    <?php endif; ?>

  <?php else: ?>
    <div style="text-align:center; color:#888; padding:30px; background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
      <p style="font-size:16px;">Chưa có dữ liệu trúng tuyển. Hãy upload file Excel để bắt đầu.</p>
    </div>
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
      <label>Họ và tên:</label>
      <input type="text" name="edit_ho_ten" id="edit_ho_ten" required>
      <label>Số báo danh:</label>
      <input type="text" name="edit_sbd" id="edit_sbd" required>
      <label>Ngày sinh:</label>
      <input type="date" name="edit_ngay_sinh" id="edit_ngay_sinh">
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeEdit()">Huỷ</button>
        <button type="submit" class="btn-save">💾 Lưu</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(id, ten, sbd, ngay) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_ho_ten').value = ten;
  document.getElementById('edit_sbd').value = sbd;

  let dateVal = '';
  if (ngay && ngay.includes('/')) {
    const parts = ngay.split('/');
    if (parts.length === 3) {
      dateVal = parts[2] + '-' + parts[1] + '-' + parts[0];
    }
  } else {
    dateVal = ngay;
  }
  document.getElementById('edit_ngay_sinh').value = dateVal;

  document.getElementById('editModal').classList.add('active');
  document.getElementById('edit_ho_ten').focus();
}
function closeEdit() {
  document.getElementById('editModal').classList.remove('active');
}
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeEdit();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeEdit();
});
</script>

</body>
</html>