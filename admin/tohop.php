<?php
session_start();
require '../config.php';

// Bảo vệ trang: chỉ Admin được truy cập
if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

// ——— Xử lý thêm tổ hợp
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] === 'add') {
    $ten = trim($_POST['ten_to_hop']);
    if ($ten !== '') {
        $stmt = $conn->prepare("INSERT INTO to_hop (ten_to_hop) VALUES (?)");
        $stmt->bind_param("s", $ten);
        $stmt->execute();
    }
}

// ——— Xử lý xoá tổ hợp
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM to_hop WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// ——— Xử lý cập nhật tổ hợp
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $ten = trim($_POST['ten_to_hop']);
    if ($ten !== '') {
        $stmt = $conn->prepare("UPDATE to_hop SET ten_to_hop = ? WHERE id = ?");
        $stmt->bind_param("si", $ten, $id);
        $stmt->execute();
    }
}

// ——— Lấy danh sách tổ hợp
$result = $conn->query("SELECT * FROM to_hop ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý tổ hợp môn</title>
    <link rel="stylesheet" href="../style.css"> <!-- Hoặc ../style.css nếu nằm trong admin/ -->
</head>
<body>
    <h2>QUẢN LÝ TỔ HỢP MÔN</h2>

    <form method="post">
        <input type="hidden" name="action" value="add">
        <input type="text" name="ten_to_hop" placeholder="Nhập tên tổ hợp" required>
        <button type="submit">➕ Thêm tổ hợp</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Tên tổ hợp</th>
                <th>Chức năng</th>
            </tr>
        </thead>
        <tbody>
            <?php $stt = 1; while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $stt++ ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="text" name="ten_to_hop" value="<?= htmlspecialchars($row['ten_to_hop']) ?>">
                        <button type="submit">💾</button>
                    </form>
                </td>
                <td>
                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Xác nhận xoá tổ hợp này?')">🗑️ Xoá</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
