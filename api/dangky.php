<?php
session_start();
require '../config.php';  // Nạp cấu hình DB_HOST, DB_USER, DB_PASS, DB_NAME

// 1. Thiết lập deadline (nếu có)
$registration_deadline = '2025-05-31 23:59:59';
if (new DateTime() > new DateTime($registration_deadline)) {
    http_response_code(403);
    die("❌ Đã hết hạn đăng ký từ {$registration_deadline}.");
}

// 2. Kết nối cơ sở dữ liệu
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    die("❌ Lỗi kết nối CSDL: " . $conn->connect_error);
}
// CỰC KỲ QUAN TRỌNG: set charset ngay để xử lý Unicode đúng
$conn->set_charset('utf8mb4');

// 3. Lấy và lọc dữ liệu từ form
$ho_ten      = trim($_POST['ho_ten']      ?? '');
$lop         = trim(filter_input(INPUT_POST, 'lop', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$so_bao_danh = trim($_POST['so_bao_danh'] ?? '');
$so_dien_thoai = trim(filter_input(INPUT_POST, 'so_dien_thoai', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$email       = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$nv1         = filter_input(INPUT_POST, 'nv1', FILTER_VALIDATE_INT);
$nv2         = filter_input(INPUT_POST, 'nv2', FILTER_VALIDATE_INT);
// --- decode nếu lỡ có entity ---
$ho_ten = html_entity_decode(
    $ho_ten,
    ENT_QUOTES | ENT_HTML5,
    'UTF-8'
);
// 4. Kiểm tra bắt buộc
if (!$ho_ten || !$lop || !$so_bao_danh || !$so_dien_thoai || !$nv1 || !$nv2) {
    http_response_code(400);
    die("❌ Vui lòng nhập đầy đủ thông tin.");
}

// 5. Ràng buộc NV1 khác NV2
if ($nv1 === $nv2) {
    http_response_code(400);
    die("❌ Nguyện vọng 1 và 2 không được trùng nhau.");
}

// 6. Kiểm tra NV1 & NV2 có hợp lệ trong bảng to_hop
$stmt = $conn->prepare("SELECT COUNT(*) FROM to_hop WHERE id IN (?, ?)");
$stmt->bind_param("ii", $nv1, $nv2);
$stmt->execute();
$stmt->bind_result($cnt);
$stmt->fetch();
$stmt->close();
if ($cnt < 2) {
    http_response_code(400);
    die("❌ Nguyện vọng không hợp lệ.");
}

// 7. Ngăn trùng SBD đã đăng ký
$stmt = $conn->prepare("SELECT id FROM hoc_sinh WHERE so_bao_danh = ?");
$stmt->bind_param("s", $so_bao_danh);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    http_response_code(409);
    die("⚠️ Số báo danh này đã đăng ký rồi.");
}
$stmt->close();

// 8. Xác thực SBD + Họ tên với bảng danh_sach_trung_tuyen
// --- chuẩn bị & bind đủ tham số ---
$sql = "SELECT id FROM danh_sach_trung_tuyen WHERE so_bao_danh = ? AND ho_ten = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $so_bao_danh, $ho_ten);
$stmt->execute();
$stmt->store_result();

// --- debug nhanh nếu cần ---
// 1) In ra giá trị input
//echo '<pre style="background:#fee;padding:10px;">';
//echo "DEBUG INPUT:\n";
//echo "  so_bao_danh = '". htmlspecialchars($so_bao_danh) ."'\n";
//echo "  ho_ten      = '". htmlspecialchars($ho_ten) ."'";

// 2) In ra số dòng MySQLi nhận được
//echo "\n\nDEBUG ROWS:\n";
//echo "  num_rows = ". $stmt->num_rows ."\n";

// 3) Dựng và in ra query hoàn chỉnh
//function interpolateQuery($conn, $sql, $params) {
//    $parts = explode("?", $sql);
//    $out   = array_shift($parts);
//    foreach ($params as $p) {
//        $out .= "'". $conn->real_escape_string(trim($p)) ."'";
//        $out .= array_shift($parts);
//    }
//    return $out;
//}
//$debugSql = interpolateQuery($conn, $sqlTpl, [$so_bao_danh, $ho_ten]);
//echo "\n\nDEBUG SQL:\n";
//echo $debugSql;
//echo "\n</pre>";

if ($stmt->num_rows === 0) {
    http_response_code(403);
    die("❌ Thông tin SBD/Họ tên không khớp danh sách trúng tuyển.");
}
$stmt->close();

// 9. Lấy label nguyện vọng từ to_hop để lưu
function getToHopLabel($conn, $id) {
    $r = $conn->query("SELECT ten_to_hop FROM to_hop WHERE id = $id")->fetch_row();
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
$stmt->bind_param(
    "sssssss",
    $ho_ten,
    $lop,
    $so_bao_danh,
    $so_dien_thoai,
    $email,
    $label1,
    $label2
);

if (!$stmt->execute()) {
    http_response_code(500);
    die("❌ Lỗi khi lưu dữ liệu: " . $stmt->error);
}

// 11. Gửi email xác nhận (nếu email hợp lệ)
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

// 12. Đặt session thông báo và redirect về index.php
$_SESSION['success'] = "✅ Đăng ký thành công! SBD: {$so_bao_danh}";
$_SESSION['sbd']     = $so_bao_danh;
header("Location: ../index.php");
exit;
?>
