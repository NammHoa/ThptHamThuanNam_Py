<?php
// api/lookupinf.php — đặt trong thư mục api/
date_default_timezone_set('Asia/Ho_Chi_Minh');
require '../config.php';

header('Content-Type: application/json; charset=utf-8');

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

$sbd    = trim($_POST['sbd']    ?? '');
$ho_ten = trim($_POST['ho_ten'] ?? '');

// Validate đầu vào
if (!$sbd || !$ho_ten) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin.']);
    exit;
}

// Kết nối DB
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}
$conn->set_charset('utf8mb4');

// Bước 1: Kiểm tra SBD có trong danh sách trúng tuyển không
$stmt = $conn->prepare("SELECT id FROM danh_sach_trung_tuyen WHERE so_bao_danh = ? AND ho_ten = ?");
$stmt->bind_param("ss", $sbd, $ho_ten);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Số báo danh hoặc họ tên không khớp danh sách trúng tuyển. Vui lòng kiểm tra lại.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Bước 2: Tìm trong bảng hoc_sinh xem đã đăng ký chưa
$stmt = $conn->prepare("
    SELECT ho_ten, lop, so_bao_danh, so_dien_thoai,
           nguyen_vong_1, nguyen_vong_2, ngay_dang_ky
    FROM hoc_sinh
    WHERE so_bao_danh = ?
    LIMIT 1
");
$stmt->bind_param("s", $sbd);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Có trong danh sách trúng tuyển nhưng chưa đăng ký
    echo json_encode([
        'success'    => true,
        'registered' => false
    ]);
} else {
    $row = $result->fetch_assoc();
    // Format ngày giờ đẹp hơn
    $ngay = date('H:i – d/m/Y', strtotime($row['ngay_dang_ky']));
    echo json_encode([
        'success'    => true,
        'registered' => true,
        'data'       => [
            'ho_ten'       => $row['ho_ten'],
            'lop'          => $row['lop'],
            'so_bao_danh'  => $row['so_bao_danh'],
            'nguyen_vong_1'=> $row['nguyen_vong_1'],
            'nguyen_vong_2'=> $row['nguyen_vong_2'],
            'ngay_dang_ky' => $ngay,
        ]
    ]);
}

$stmt->close();
$conn->close();