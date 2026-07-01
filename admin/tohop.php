<?php
session_start();
require '../config.php';

if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

$message      = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $ten = trim($_POST['ten_to_hop']);
        if ($ten !== '') {
            $stmt = $conn->prepare("INSERT INTO to_hop (ten_to_hop) VALUES (?)");
            $stmt->bind_param("s", $ten);
            $stmt->execute();
            $message      = "Đã thêm tổ hợp thành công!";
            $message_type = 'success';
        }
    }
    if ($_POST['action'] === 'edit') {
        $id  = intval($_POST['id']);
        $ten = trim($_POST['ten_to_hop']);
        if ($ten !== '') {
            $stmt = $conn->prepare("UPDATE to_hop SET ten_to_hop = ? WHERE id = ?");
            $stmt->bind_param("si", $ten, $id);
            $stmt->execute();
            $message      = "Đã cập nhật tổ hợp thành công!";
            $message_type = 'success';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM to_hop WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $message      = "Đã xoá tổ hợp thành công!";
    $message_type = 'success';
}

$result = $conn->query("SELECT * FROM to_hop ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý tổ hợp môn</title>
  <link rel="stylesheet" href="admin_style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">
  <style>
    .btn-header { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:6px; background:rgba(255,255,255,0.15); color:#fff; font-size:14px; font-weight:500; text-decoration:none; border:1px solid rgba(255,255,255,0.25); transition:all 0.2s; }
    .btn-header:hover { background:rgba(255,255,255,0.28); color:#fff; text-decoration:none; }
    .btn-header-danger { background:rgba(220,53,69,0.7); border-color:rgba(220,53,69,0.5); }
    .btn-header-danger:hover { background:#dc3545; }

    .toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px; }
    .toolbar h2 { margin:0; color:#004080; font-size:18px; }
    .btn-add { display:inline-flex; align-items:center; gap:7px; padding:10px 20px; background:linear-gradient(135deg,#28a745,#20c050); color:#fff; border:none; border-radius:8px; font-size:15px; font-weight:bold; cursor:pointer; box-shadow:0 2px 6px rgba(40,167,69,0.3); transition:background 0.2s, transform 0.1s; white-space:nowrap; }
    .btn-add:hover { background:linear-gradient(135deg,#218838,#1aab42); transform:translateY(-1px); }

    .alert { display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:8px; margin-bottom:15px; font-size:14px; }
    .alert-success { background:#d4edda; color:#155724; border-left:4px solid #28a745; }

    .tohop-table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.08); }
    .tohop-table th { background:linear-gradient(135deg,#003366,#004080); color:#fff; padding:13px 15px; text-align:left; font-size:14px; }
    .tohop-table td { padding:13px 15px; border-bottom:1px solid #f0f0f0; font-size:14px; vertical-align:middle; }
    .tohop-table tr:last-child td { border-bottom:none; }
    .tohop-table tr:hover td { background:#f8f9ff; }
    .stt-badge { background:#004080; color:#fff; border-radius:50%; width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:bold; }
    .action-btns { display:flex; gap:8px; }
    .btn-edit { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; background:#007bff; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:13px; transition:background 0.2s; white-space:nowrap; }
    .btn-edit:hover { background:#0056b3; }
    .btn-delete { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; background:#dc3545; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:13px; text-decoration:none; transition:background 0.2s; white-space:nowrap; }
    .btn-delete:hover { background:#c82333; color:#fff; text-decoration:none; }

    .tohop-card-list { display:none; }
    .tohop-card { background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1); padding:15px; margin-bottom:12px; }
    .tohop-card-header { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:12px; padding-bottom:10px; border-bottom:2px solid #f0f4f8; }
    .tohop-card-name { font-weight:bold; font-size:15px; color:#004080; flex:1; line-height:1.4; }
    .tohop-card-stt { background:#004080; color:#fff; border-radius:50%; width:30px; height:30px; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:bold; flex-shrink:0; }
    .tohop-card-actions { display:flex; gap:8px; }
    .tohop-card-actions .btn-edit, .tohop-card-actions .btn-delete { flex:1; justify-content:center; padding:9px; font-size:14px; }

    .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; padding:15px; }
    .modal-overlay.active { display:flex; }
    .modal { background:#fff; border-radius:12px; padding:25px; width:100%; max-width:480px; box-shadow:0 10px 40px rgba(0,0,0,0.2); animation:slideIn 0.2s ease; }
    @keyframes slideIn { from{transform:translateY(-20px);opacity:0} to{transform:translateY(0);opacity:1} }
    .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:12px; border-bottom:2px solid #f0f4f8; }
    .modal-title { font-size:18px; font-weight:bold; color:#004080; margin:0; display:flex; align-items:center; gap:8px; }
    .modal-close { background:none; border:none; font-size:24px; cursor:pointer; color:#888; line-height:1; padding:0; }
    .modal-close:hover { color:#333; }
    .modal label { display:block; margin-bottom:6px; font-weight:bold; color:#444; font-size:14px; }
    .modal input[type="text"] { width:100%; padding:11px 14px; border:1px solid #ccc; border-radius:8px; font-size:15px; margin-bottom:20px; transition:border 0.2s; box-sizing:border-box; }
    .modal input[type="text"]:focus { border-color:#004080; outline:none; box-shadow:0 0 0 3px rgba(0,64,128,0.1); }
    .modal-footer { display:flex; gap:10px; justify-content:flex-end; }
    .btn-cancel { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; background:#e0e0e0; color:#555; border:none; border-radius:8px; font-size:14px; cursor:pointer; }
    .btn-cancel:hover { background:#ccc; }
    .btn-confirm { display:inline-flex; align-items:center; gap:6px; padding:10px 24px; background:linear-gradient(135deg,#28a745,#20c050); color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:bold; cursor:pointer; }
    .btn-confirm:hover { background:linear-gradient(135deg,#218838,#1aab42); }

    @media (max-width:600px) {
      .tohop-table-wrapper { display:none; }
      .tohop-card-list { display:block; }
      .toolbar { flex-direction:column; align-items:stretch; }
      .btn-add { width:100%; justify-content:center; }
      .modal-footer { flex-direction:column-reverse; }
      .btn-cancel, .btn-confirm { width:100%; justify-content:center; padding:12px; }
    }
  </style>
</head>
<body>

<header>
  <h1>Quản lý tổ hợp môn</h1>
  <div class="btn-group">
    <a href="dashboard.php" class="btn-header"><i class="ti ti-home"></i> Dashboard</a>
    <a href="logout.php" class="btn-header btn-header-danger"><i class="ti ti-logout"></i> Đăng xuất</a>
  </div>
</header>

<main>
  <?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?>">
      <i class="ti ti-circle-check"></i>
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <div class="toolbar">
    <h2>Danh sách tổ hợp môn</h2>
    <button class="btn-add" onclick="openModal('add')">
      <i class="ti ti-plus"></i> Thêm tổ hợp
    </button>
  </div>

  <?php if ($result && $result->num_rows > 0):
    $rows = $result->fetch_all(MYSQLI_ASSOC);
  ?>

  <div class="tohop-table-wrapper">
    <table class="tohop-table">
      <thead>
        <tr>
          <th style="width:60px; text-align:center;">STT</th>
          <th>Tên tổ hợp môn</th>
          <th style="width:160px; text-align:center;">Chức năng</th>
        </tr>
      </thead>
      <tbody>
        <?php $stt = 1; foreach ($rows as $row): ?>
        <tr>
          <td style="text-align:center;"><span class="stt-badge"><?= $stt++ ?></span></td>
          <td><?= htmlspecialchars($row['ten_to_hop']) ?></td>
          <td>
            <div class="action-btns">
              <button class="btn-edit"
                onclick="openModal('edit', <?= $row['id'] ?>, '<?= addslashes(htmlspecialchars($row['ten_to_hop'])) ?>')">
                <i class="ti ti-edit"></i> Sửa
              </button>
              <a href="?delete=<?= $row['id'] ?>" class="btn-delete"
                 onclick="return confirm('Xác nhận xoá tổ hợp này?')">
                <i class="ti ti-trash"></i> Xoá
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="tohop-card-list">
    <?php $stt = 1; foreach ($rows as $row): ?>
    <div class="tohop-card">
      <div class="tohop-card-header">
        <div class="tohop-card-stt"><?= $stt++ ?></div>
        <div class="tohop-card-name"><?= htmlspecialchars($row['ten_to_hop']) ?></div>
      </div>
      <div class="tohop-card-actions">
        <button class="btn-edit"
          onclick="openModal('edit', <?= $row['id'] ?>, '<?= addslashes(htmlspecialchars($row['ten_to_hop'])) ?>')">
          <i class="ti ti-edit"></i> Sửa
        </button>
        <a href="?delete=<?= $row['id'] ?>" class="btn-delete"
           onclick="return confirm('Xác nhận xoá tổ hợp này?')">
          <i class="ti ti-trash"></i> Xoá
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php else: ?>
    <div style="text-align:center; color:#888; padding:40px; background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
      <p style="font-size:16px; margin-bottom:15px;">Chưa có tổ hợp nào.</p>
      <button class="btn-add" onclick="openModal('add')" style="margin:auto;">
        <i class="ti ti-plus"></i> Thêm tổ hợp đầu tiên
      </button>
    </div>
  <?php endif; ?>
</main>

<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle">
        <i class="ti ti-plus" id="modalIcon"></i>
        <span id="modalTitleText">Thêm tổ hợp</span>
      </h3>
      <button class="modal-close" onclick="closeModal()">×</button>
    </div>
    <form method="POST" id="modalForm">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="id" id="formId" value="">
      <label>Tên tổ hợp môn:</label>
      <input type="text" name="ten_to_hop" id="formTen"
             placeholder="Ví dụ: Vật lí, Hóa học, Sinh học, Tin học" required>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal()">
          <i class="ti ti-x"></i> Huỷ
        </button>
        <button type="submit" class="btn-confirm" id="btnConfirm">
          <i class="ti ti-plus" id="btnIcon"></i>
          <span id="btnText">Thêm</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(mode, id = '', ten = '') {
  const overlay   = document.getElementById('modalOverlay');
  const action    = document.getElementById('formAction');
  const formId    = document.getElementById('formId');
  const formTen   = document.getElementById('formTen');
  const modalIcon = document.getElementById('modalIcon');
  const titleText = document.getElementById('modalTitleText');
  const btnIcon   = document.getElementById('btnIcon');
  const btnText   = document.getElementById('btnText');

  if (mode === 'add') {
    modalIcon.className = 'ti ti-plus';
    titleText.textContent = 'Thêm tổ hợp môn';
    action.value  = 'add';
    formId.value  = '';
    formTen.value = '';
    btnIcon.className = 'ti ti-plus';
    btnText.textContent = 'Thêm';
  } else {
    modalIcon.className = 'ti ti-edit';
    titleText.textContent = 'Sửa tổ hợp môn';
    action.value  = 'edit';
    formId.value  = id;
    formTen.value = ten;
    btnIcon.className = 'ti ti-device-floppy';
    btnText.textContent = 'Lưu';
  }

  overlay.classList.add('active');
  setTimeout(() => formTen.focus(), 100);
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('active');
}

document.getElementById('modalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});
</script>

<script>
const alertBox = document.querySelector('.alert');
if (alertBox) {
  setTimeout(() => {
    alertBox.style.transition = 'opacity 0.5s ease';
    alertBox.style.opacity = '0';
    setTimeout(() => alertBox.style.display = 'none', 500);
  }, 3000);
}
</script>

</body>
</html>