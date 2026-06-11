<?php
session_start();
require_once '../config.php';

// ============================================================
// CẤU HÌNH GMAIL
// ============================================================
define('MAIL_FROM',     'namdeptrai270304@gmail.com');
define('MAIL_PASSWORD', 'odqmmvjzfqndktao');
define('MAIL_NAME',     'Trường THPT Hàm Thuận Nam');
// ============================================================

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. Kiểm tra deadline từ DB
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    die("❌ Lỗi kết nối CSDL: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$res = $conn->query("SELECT gia_tri FROM thietlap WHERE ten='han_dang_ky' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    if (new DateTime() > new DateTime($row['gia_tri'])) {
        http_response_code(403);
        die("❌ Đã hết hạn đăng ký.");
    }
}

// 2. Lấy và lọc dữ liệu từ form
$ho_ten        = trim($_POST['ho_ten']        ?? '');
$lop           = trim(filter_input(INPUT_POST, 'lop',           FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$so_bao_danh   = trim($_POST['so_bao_danh']   ?? '');
$so_dien_thoai = trim(filter_input(INPUT_POST, 'so_dien_thoai', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
$email         = trim(filter_input(INPUT_POST, 'email',         FILTER_SANITIZE_EMAIL));
$nv1           = filter_input(INPUT_POST, 'nv1', FILTER_VALIDATE_INT);
$nv2           = filter_input(INPUT_POST, 'nv2', FILTER_VALIDATE_INT);
$ho_ten        = html_entity_decode($ho_ten, ENT_QUOTES | ENT_HTML5, 'UTF-8');

// 3. Kiểm tra bắt buộc
if (!$ho_ten || !$lop || !$so_bao_danh || !$so_dien_thoai || !$nv1 || !$nv2) {
    http_response_code(400);
    die("❌ Vui lòng nhập đầy đủ thông tin.");
}

// 4. NV1 khác NV2
if ($nv1 === $nv2) {
    http_response_code(400);
    die("❌ Nguyện vọng 1 và 2 không được trùng nhau.");
}

// 5. Kiểm tra NV hợp lệ
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

// 6. Kiểm tra SBD đã đăng ký chưa
$stmt = $conn->prepare("SELECT id FROM hoc_sinh WHERE so_bao_danh = ?");
$stmt->bind_param("s", $so_bao_danh);
$stmt->execute();
$stmt->store_result();
// if ($stmt->num_rows > 0) {
//     http_response_code(409);
//     die("⚠️ Số báo danh này đã đăng ký rồi.");
// }
if ($stmt->num_rows > 0) {
    $_SESSION['error'] = "⚠️ Số báo danh <strong>{$so_bao_danh}</strong> đã được đăng ký trước đó.";
    header("Location: ../index.php");
    exit;
}
$stmt->close();

// 7. Xác thực SBD + Họ tên với danh sách trúng tuyển
$stmt = $conn->prepare("SELECT id FROM danh_sach_trung_tuyen WHERE so_bao_danh = ? AND ho_ten = ?");
$stmt->bind_param("ss", $so_bao_danh, $ho_ten);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    http_response_code(403);
    die("❌ Thông tin SBD/Họ tên không khớp danh sách trúng tuyển.");
}
$stmt->close();

// 8. Lấy tên nguyện vọng
function getToHopLabel($conn, $id) {
    $r = $conn->query("SELECT ten_to_hop FROM to_hop WHERE id = $id")->fetch_row();
    return $r[0] ?? '';
}
$label1 = getToHopLabel($conn, $nv1);
$label2 = getToHopLabel($conn, $nv2);

// 9. Lưu vào DB
$stmt = $conn->prepare(
    "INSERT INTO hoc_sinh (ho_ten, lop, so_bao_danh, so_dien_thoai, email, nguyen_vong_1, nguyen_vong_2)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssssss", $ho_ten, $lop, $so_bao_danh, $so_dien_thoai, $email, $label1, $label2);
if (!$stmt->execute()) {
    http_response_code(500);
    die("❌ Lỗi khi lưu dữ liệu: " . $stmt->error);
}

// 10. Gửi email xác nhận bằng PHPMailer
if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->SMTPDebug  = 0;       
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_FROM;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_NAME);
        $mail->addAddress($email, $ho_ten);

        $mail->isHTML(true);
        $mail->Subject = '✅ Xác nhận đăng ký nguyện vọng lớp 10';
        $mail->Body    = "
        <div style='font-family:Arial,sans-serif; max-width:600px; margin:auto; border:1px solid #ddd; border-radius:8px; overflow:hidden;'>
          <div style='background:#004080; padding:20px; text-align:center;'>
            <h2 style='color:#fff; margin:0;'>TRƯỜNG THPT HÀM THUẬN NAM</h2>
            <p style='color:#cce0ff; margin:5px 0 0;'>Xác nhận đăng ký nguyện vọng lớp 10</p>
          </div>
          <div style='padding:25px;'>
            <p>Xin chào <strong>{$ho_ten}</strong>,</p>
            <p>Bạn đã đăng ký nguyện vọng thành công. Dưới đây là thông tin của bạn:</p>
            <table style='width:100%; border-collapse:collapse; margin-top:15px;'>
              <tr style='background:#f0f4f8;'>
                <td style='padding:10px; border:1px solid #ddd; font-weight:bold; width:40%;'>Họ và tên</td>
                <td style='padding:10px; border:1px solid #ddd;'>{$ho_ten}</td>
              </tr>
              <tr>
                <td style='padding:10px; border:1px solid #ddd; font-weight:bold;'>Lớp</td>
                <td style='padding:10px; border:1px solid #ddd;'>{$lop}</td>
              </tr>
              <tr style='background:#f0f4f8;'>
                <td style='padding:10px; border:1px solid #ddd; font-weight:bold;'>Số báo danh</td>
                <td style='padding:10px; border:1px solid #ddd;'>{$so_bao_danh}</td>
              </tr>
              <tr>
                <td style='padding:10px; border:1px solid #ddd; font-weight:bold;'>Nguyện vọng 1</td>
                <td style='padding:10px; border:1px solid #ddd; color:#004080;'><strong>{$label1}</strong></td>
              </tr>
              <tr style='background:#f0f4f8;'>
                <td style='padding:10px; border:1px solid #ddd; font-weight:bold;'>Nguyện vọng 2</td>
                <td style='padding:10px; border:1px solid #ddd; color:#004080;'><strong>{$label2}</strong></td>
              </tr>
            </table>
            <p style='margin-top:20px; color:#555; font-size:13px;'>
              Nếu có thắc mắc, vui lòng liên hệ nhà trường.<br>
              Trân trọng,<br>
              <strong>Trường THPT Hàm Thuận Nam</strong>
            </p>
          </div>
          <div style='background:#f0f4f8; padding:12px; text-align:center; font-size:12px; color:#888;'>
            Email này được gửi tự động, vui lòng không trả lời.
          </div>
        </div>";

        $mail->send();

    } catch (Exception $e) {
        error_log("Gửi mail thất bại: " . $mail->ErrorInfo);
    }
    // } catch (Exception $e) {
    // die("❌ Lỗi gửi mail: " . $mail->ErrorInfo);
}   

// 11. Redirect về trang chủ
$_SESSION['success'] = "✅ Đăng ký thành công! Email xác nhận đã được gửi đến hộp thư của bạn. Nếu không thấy, vui lòng kiểm tra mục Spam hoặc liên hệ nhà trường.";
$_SESSION['sbd']     = $so_bao_danh;
header("Location: ../index.php");
exit;
?>