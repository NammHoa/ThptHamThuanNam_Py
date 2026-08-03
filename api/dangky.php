<?php
session_start();
require '../config.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function redirectError($msg) {
    $_SESSION['error'] = $msg;
    header("Location: ../index.php");
    exit;
}

// 1. Kết nối DB
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) redirectError("❌ Lỗi kết nối CSDL.");
$conn->set_charset('utf8mb4');

// 2. Kiểm tra deadline
$res = $conn->query("SELECT gia_tri FROM thietlap WHERE ten='han_dang_ky' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $deadline = new DateTime($row['gia_tri']);
    if (new DateTime() > $deadline) {
        redirectError("❌ Đã hết hạn đăng ký từ " . $deadline->format('d/m/Y H:i') . ".");
    }
}

// 3. Lấy dữ liệu từ form
$ho_ten        = trim($_POST['ho_ten']        ?? '');
$ngay_sinh     = trim($_POST['ngay_sinh']     ?? '');
$lop           = trim($_POST['lop']           ?? '');
$so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '');
$email         = trim($_POST['email']         ?? '');
$nv1           = filter_input(INPUT_POST, 'nv1', FILTER_VALIDATE_INT);
$nv2           = filter_input(INPUT_POST, 'nv2', FILTER_VALIDATE_INT);

if (!$ho_ten || !$ngay_sinh || !$lop || !$so_dien_thoai || !$nv1 || !$nv2) {
    redirectError("❌ Vui lòng nhập đầy đủ thông tin.");
}

$dt = DateTime::createFromFormat('Y-m-d', $ngay_sinh);
if (!$dt || $dt >= new DateTime()) {
    redirectError("❌ Ngày sinh không hợp lệ.");
}
$ngay_sinh_display = $dt->format('d/m/Y');

if (!preg_match('/^(03|05|07|08|09)\d{8}$/', $so_dien_thoai)) {
    redirectError("❌ Số điện thoại không hợp lệ.");
}

if (!preg_match('/^[^\s@]+@gmail\.com$/', $email)) {
    redirectError("❌ Email phải có đuôi @gmail.com.");
}

if ($nv1 === $nv2) {
    redirectError("❌ Nguyện vọng 1 và 2 không được trùng nhau.");
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM to_hop WHERE id IN (?, ?)");
$stmt->bind_param("ii", $nv1, $nv2);
$stmt->execute();
$stmt->bind_result($cnt);
$stmt->fetch();
$stmt->close();
if ($cnt < 2) {
    redirectError("❌ Nguyện vọng không hợp lệ.");
}

// 10. Kiểm tra đã đăng ký chưa
$stmt = $conn->prepare("SELECT id FROM hoc_sinh WHERE ho_ten = ? AND ngay_sinh = ? AND lop = ?");
$stmt->bind_param("sss", $ho_ten, $ngay_sinh_display, $lop);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    redirectError("⚠️ Học sinh này đã đăng ký rồi.");
}
$stmt->close();

// 11. Xác thực họ tên + ngày sinh với danh sách trúng tuyển
$stmt = $conn->prepare("SELECT id FROM danh_sach_trung_tuyen WHERE ho_ten = ? AND ngay_sinh = ?");
$stmt->bind_param("ss", $ho_ten, $ngay_sinh_display);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    redirectError("❌ Họ tên hoặc ngày sinh không khớp danh sách trúng tuyển. Vui lòng kiểm tra lại.");
}
$stmt->close();

// 12. Lấy label tổ hợp
function getToHopLabel($conn, $id) {
    $stmt = $conn->prepare("SELECT ten_to_hop FROM to_hop WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($label);
    $stmt->fetch();
    $stmt->close();
    return $label ?? '';
}
$label1 = getToHopLabel($conn, $nv1);
$label2 = getToHopLabel($conn, $nv2);

// 13. Lưu vào DB
$stmt = $conn->prepare("
    INSERT INTO hoc_sinh (ho_ten, ngay_sinh, lop, so_dien_thoai, email, nguyen_vong_1, nguyen_vong_2)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("sssssss", $ho_ten, $ngay_sinh_display, $lop, $so_dien_thoai, $email, $label1, $label2);
if (!$stmt->execute()) {
    redirectError("❌ Lỗi khi lưu dữ liệu: " . $stmt->error);
}
$stmt->close();

// 14. Lưu vào mail_queue thay vì gửi ngay
if ($email) {
    $stmt = $conn->prepare("
        INSERT INTO mail_queue (ho_ten, email, ngay_sinh, lop, nv1, nv2)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssss",
        $ho_ten,
        $email,
        $ngay_sinh_display,
        $lop,
        $label1,
        $label2
    );
    $stmt->execute();
    $stmt->close();
}

// 15. Lưu session và redirect
$_SESSION['dang_ky_thanh_cong'] = [
    'ho_ten'        => $ho_ten,
    'ngay_sinh'     => $ngay_sinh_display,
    'lop'           => $lop,
    'so_dien_thoai' => $so_dien_thoai,
    'email'         => $email,
    'nv1'           => $label1,
    'nv2'           => $label2,
];
header("Location: ../success.php");
exit;