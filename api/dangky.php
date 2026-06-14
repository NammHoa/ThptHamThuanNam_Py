<?php
session_start();
require '../config.php';

// 1. Kết nối cơ sở dữ liệu
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    die("❌ Lỗi kết nối CSDL: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// 2. Đọc deadline từ DB (không hardcode)
$res = $conn->query("SELECT gia_tri FROM thietlap WHERE ten='han_dang_ky' LIMIT 1");
$row = $res->fetch_assoc();

if (!$row || empty($row['gia_tri'])) {
    // Chưa cấu hình deadline → không cho đăng ký
    $_SESSION['error'] = "⚠️ Hệ thống chưa mở đăng ký. Vui lòng thử lại sau.";
    header("Location: ../index.php");
    exit;
}

$deadline = new DateTime($row['gia_tri']);
$now      = new DateTime();

if ($now > $deadline) {
    // Đã hết hạn
    $_SESSION['error'] = "❌ Đã hết hạn đăng ký từ " . $deadline->format('d/m/Y H:i') . ".";
    header("Location: ../index.php");
    exit;
}

// 3. Lấy và lọc dữ liệu từ form
$ho_ten        = trim($_POST['ho_ten']      ?? '');
$lop           = trim(filter_input(INPUT_POST, 'lop',           FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$so_bao_danh   = trim($_POST['so_bao_danh'] ?? '');
$so_dien_thoai = trim(filter_input(INPUT_POST, 'so_dien_thoai', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$email         = trim(filter_input(INPUT_POST, 'email',         FILTER_SANITIZE_EMAIL));
$nv1           = filter_input(INPUT_POST, 'nv1', FILTER_VALIDATE_INT);
$nv2           = filter_input(INPUT_POST, 'nv2', FILTER_VALIDATE_INT);

// Decode entity nếu có
$ho_ten = html_entity_decode($ho_ten, ENT_QUOTES | ENT_HTML5, 'UTF-8');

// 4. Kiểm tra bắt buộc
if (!$ho_ten || !$lop || !$so_bao_danh || !$so_dien_thoai || !$nv1 || !$nv2) {
    $_SESSION['error'] = "❌ Vui lòng nhập đầy đủ thông tin.";
    header("Location: ../index.php");
    exit;
}

// 5. NV1 khác NV2
if ($nv1 === $nv2) {
    $_SESSION['error'] = "❌ Nguyện vọng 1 và 2 không được trùng nhau.";
    header("Location: ../index.php");
    exit;
}

// 6. Kiểm tra NV1 & NV2 hợp lệ trong bảng to_hop
$stmt = $conn->prepare("SELECT COUNT(*) FROM to_hop WHERE id IN (?, ?)");
$stmt->bind_param("ii", $nv1, $nv2);
$stmt->execute();
$stmt->bind_result($cnt);
$stmt->fetch();
$stmt->close();
if ($cnt < 2) {
    $_SESSION['error'] = "❌ Nguyện vọng không hợp lệ.";
    header("Location: ../index.php");
    exit;
}

// 7. Ngăn trùng SBD
$stmt = $conn->prepare("SELECT id FROM hoc_sinh WHERE so_bao_danh = ?");
$stmt->bind_param("s", $so_bao_danh);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $_SESSION['error'] = "⚠️ Số báo danh này đã đăng ký rồi.";
    header("Location: ../index.php");
    exit;
}
$stmt->close();

// 8. Xác thực SBD + Họ tên với danh sách trúng tuyển
$stmt = $conn->prepare("SELECT id FROM danh_sach_trung_tuyen WHERE so_bao_danh = ? AND ho_ten = ?");
$stmt->bind_param("ss", $so_bao_danh, $ho_ten);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $_SESSION['error'] = "❌ Thông tin SBD/Họ tên không khớp danh sách trúng tuyển.";
    header("Location: ../index.php");
    exit;
}
$stmt->close();

// 9. Lấy label nguyện vọng từ to_hop
function getToHopLabel($conn, $id) {
    $r = $conn->query("SELECT ten_to_hop FROM to_hop WHERE id = " . intval($id))->fetch_row();
    return $r[0] ?? '';
}
$label1 = getToHopLabel($conn, $nv1);
$label2 = getToHopLabel($conn, $nv2);

// 10. Lưu vào bảng hoc_sinh
$stmt = $conn->prepare(
    "INSERT INTO hoc_sinh
    (ho_ten, lop, so_bao_danh, so_dien_thoai, email, nguyen_vong_1, nguyen_vong_2)
    VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssssss", $ho_ten, $lop, $so_bao_danh, $so_dien_thoai, $email, $label1, $label2);
if (!$stmt->execute()) {
    $_SESSION['error'] = "❌ Lỗi khi lưu dữ liệu: " . $stmt->error;
    header("Location: ../index.php");
    exit;
}

// 11. Gửi email xác nhận nếu có
if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $subject = "Xác nhận đăng ký nguyện vọng lớp 10";
    $body    = "
    <html><body>
      <h3>Xin chào {$ho_ten},</h3>
      <p>Bạn đã đăng ký nguyện vọng thành công:</p>
      <ul>
        <li><strong>NV1:</strong> {$label1}</li>
        <li><strong>NV2:</strong> {$label2}</li>
      </ul>
      <p>Trân trọng,<br>Trường THPT Hàm Thuận Nam</p>
    </body></html>";
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: no-reply@thamthuannam.edu.vn";
    mail($email, $subject, $body, $headers);
}

// 12. Thông báo thành công và redirect
$_SESSION['success'] = "✅ Đăng ký thành công! SBD: {$so_bao_danh}";
header("Location: ../index.php");
exit;
?>