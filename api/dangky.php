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
                <td style='padding:10px 14px;font-size:13px;color:#888;font-weight:bold;'>Số báo danh</td>
                <td style='padding:10px 14px;font-size:14px;color:#333;font-weight:bold;'>{$so_bao_danh}</td>
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

// 12. Thông báo thành công và redirect
$_SESSION['dang_ky_thanh_cong'] = [
    'ho_ten' => $ho_ten,
    'lop'    => $lop,
    'sbd'    => $so_bao_danh,
    'nv1'    => $label1,
    'nv2'    => $label2,
];
header("Location: ../success.php");
exit;
?>