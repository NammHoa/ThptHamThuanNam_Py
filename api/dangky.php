<?php
session_start();
require '../config.php';
require '../vendor/autoload.php';

// 1. Kết nối DB
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    die("❌ Lỗi kết nối CSDL.");
}
$conn->set_charset('utf8mb4');

// 2. Kiểm tra deadline
$res = $conn->query("SELECT gia_tri FROM thietlap WHERE ten='han_dang_ky' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $deadline = new DateTime($row['gia_tri']);
    if (new DateTime() > $deadline) {
        http_response_code(403);
        die("❌ Đã hết hạn đăng ký từ " . $deadline->format('d/m/Y H:i') . ".");
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

// 4. Validate bắt buộc
if (!$ho_ten || !$ngay_sinh || !$lop || !$so_dien_thoai || !$nv1 || !$nv2) {
    http_response_code(400);
    die("❌ Vui lòng nhập đầy đủ thông tin.");
}

// 5. Validate ngày sinh
$dt = DateTime::createFromFormat('Y-m-d', $ngay_sinh);
if (!$dt || $dt >= new DateTime()) {
    http_response_code(400);
    die("❌ Ngày sinh không hợp lệ.");
}
$ngay_sinh_display = $dt->format('d/m/Y');

// 6. Validate SĐT
if (!preg_match('/^(03|05|07|08|09)\d{8}$/', $so_dien_thoai)) {
    http_response_code(400);
    die("❌ Số điện thoại không hợp lệ. Phải là 10 số bắt đầu 03x, 05x, 07x, 08x hoặc 09x.");
}

// 7. Validate email
if (!preg_match('/^[^\s@]+@gmail\.com$/', $email)) {
    http_response_code(400);
    die("❌ Email phải có đuôi @gmail.com.");
}

// 8. NV1 khác NV2
if ($nv1 === $nv2) {
    http_response_code(400);
    die("❌ Nguyện vọng 1 và 2 không được trùng nhau.");
}

// 9. Kiểm tra NV hợp lệ
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

// 10. Kiểm tra đã đăng ký chưa
$stmt = $conn->prepare("SELECT id FROM hoc_sinh WHERE ho_ten = ? AND ngay_sinh = ?");
$stmt->bind_param("ss", $ho_ten, $ngay_sinh_display);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    http_response_code(409);
    die("⚠️ Học sinh này đã đăng ký rồi.");
}
$stmt->close();

// 11. Xác thực họ tên + ngày sinh với danh sách trúng tuyển
$stmt = $conn->prepare("SELECT id FROM danh_sach_trung_tuyen WHERE ho_ten = ? AND ngay_sinh = ?");
$stmt->bind_param("ss", $ho_ten, $ngay_sinh_display);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    http_response_code(403);
    die("❌ Họ tên hoặc ngày sinh không khớp danh sách trúng tuyển. Vui lòng kiểm tra lại.");
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
    http_response_code(500);
    die("❌ Lỗi khi lưu dữ liệu: " . $stmt->error);
}
$stmt->close();

// 14. Gửi email xác nhận
if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    require_once __DIR__ . '/../vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_FROM;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_NAME);
        $mail->addAddress($email, $ho_ten);

        $mail->isHTML(true);
        $mail->Subject = "Xác nhận đăng ký nguyện vọng lớp 10";
        $mail->Subject = "✅ Xác nhận đăng ký nguyện vọng lớp 10 – " . $ho_ten;
$mail->Body    = "
<!DOCTYPE html>
<html lang='vi'>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0' style='background:#f0f4f8;padding:30px 0;'>
    <tr><td align='center'>
      <table width='560' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);'>
        
        <!-- HEADER -->
        <tr>
          <td style='background:linear-gradient(135deg,#003366,#004080,#0066cc);padding:28px 32px;text-align:center;'>
            <h1 style='color:#fff;margin:0 0 6px;font-size:20px;letter-spacing:1px;'>TRƯỜNG THPT HÀM THUẬN NAM</h1>
            <p style='color:rgba(255,255,255,0.8);margin:0;font-size:13px;'>Xác nhận đăng ký nguyện vọng lớp 10</p>
          </td>
        </tr>

        <!-- BODY -->
        <tr>
          <td style='padding:28px 32px;'>
            <p style='color:#333;font-size:15px;margin:0 0 16px;'>Xin chào <strong style='color:#004080;'>{$ho_ten}</strong>,</p>
            <p style='color:#555;font-size:14px;margin:0 0 20px;line-height:1.7;'>Bạn đã đăng ký nguyện vọng tuyển sinh lớp 10 thành công. Dưới đây là thông tin của bạn:</p>

            <!-- INFO TABLE -->
            <table width='100%' cellpadding='0' cellspacing='0' style='border-radius:8px;overflow:hidden;border:1px solid #e0e8f0;margin-bottom:20px;'>
              <tr style='background:#f0f4f8;'>
                <td style='padding:10px 14px;font-size:13px;color:#888;font-weight:bold;width:140px;'>Họ và tên</td>
                <td style='padding:10px 14px;font-size:14px;color:#333;font-weight:500;'>{$ho_ten}</td>
              </tr>
              <tr style='background:#fff;'>
                <td style='padding:10px 14px;font-size:13px;color:#888;font-weight:bold;'>Lớp</td>
                <td style='padding:10px 14px;font-size:14px;color:#333;'>{$lop}</td>
              </tr>
              <tr style='background:#f0f4f8;'>
                <td style='padding:10px 14px;font-size:13px;color:#888;font-weight:bold;'>Ngày sinh</td>
                <td style='padding:10px 14px;font-size:14px;color:#333;'>{$ngay_sinh_display}</td>
              </tr>
              <tr style='background:#fff;'>
                <td style='padding:10px 14px;font-size:13px;color:#888;font-weight:bold;'>Nguyện vọng 1</td>
                <td style='padding:10px 14px;font-size:14px;color:#1565c0;font-weight:500;'>{$label1}</td>
              </tr>
              <tr style='background:#f0f4f8;'>
                <td style='padding:10px 14px;font-size:13px;color:#888;font-weight:bold;'>Nguyện vọng 2</td>
                <td style='padding:10px 14px;font-size:14px;color:#1b7a5a;font-weight:500;'>{$label2}</td>
              </tr>
            </table>

            <p style='color:#555;font-size:13px;line-height:1.7;margin:0;'>Nếu có thắc mắc, vui lòng liên hệ nhà trường.<br>Trân trọng,<br><strong>Trường THPT Hàm Thuận Nam</strong></p>
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style='background:#f8f9fa;padding:16px 32px;text-align:center;border-top:1px solid #e0e0e0;'>
            <p style='color:#aaa;font-size:12px;margin:0;'>📍 18 Trần Phú, Xã Hàm Thuận Nam, Tỉnh Lâm Đồng</p>
            <p style='color:#aaa;font-size:12px;margin:4px 0 0;'>📞 02523867255 &nbsp;|&nbsp; 📧 c3hamthuannam.binhthuan@moet.edu.vn</p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Lỗi gửi mail: " . $mail->ErrorInfo);
    }
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