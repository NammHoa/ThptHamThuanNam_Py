<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

// Lấy 50 email chưa gửi mỗi lần chạy
$result = $conn->query("
    SELECT * FROM mail_queue
    WHERE sent = 0
    ORDER BY id ASC
    LIMIT 50
");

$count = 0;

while ($row = $result->fetch_assoc()) {

    // Đếm số email đã gửi hôm nay
    $r = $conn->query("
        SELECT COUNT(*) FROM mail_queue
        WHERE sent = 1 AND DATE(sent_at) = CURDATE()
    ");
    $soHomNay = $r->fetch_row()[0];

    // Dưới 450 dùng Gmail 1, từ 450 trở lên dùng Gmail 2
    if ($soHomNay <= 450) {
        $mailUser = MAIL_FROM;
        $mailPass = MAIL_PASSWORD;
    } else {
        $mailUser = defined('MAIL_FROM_2')    ? MAIL_FROM_2    : MAIL_FROM;
        $mailPass = defined('MAIL_PASSWORD_2') ? MAIL_PASSWORD_2 : MAIL_PASSWORD;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailUser;
        $mail->Password   = $mailPass;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';

        $mail->setFrom($mailUser, 'Trường THPT Hàm Thuận Nam');
        $mail->addAddress($row['email'], $row['ho_ten']);
        $mail->isHTML(true);
        $mail->Subject = '=?UTF-8?B?' . base64_encode('Xác nhận đăng ký nguyện vọng lớp 10') . '?=';
        $mail->AltBody = "Xin chào {$row['ho_ten']}, bạn đã đăng ký thành công. NV1: {$row['nv1']}, NV2: {$row['nv2']}.";
        $mail->Body    = "
        <html><body style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>
          <div style='background:#004080;padding:20px;text-align:center;border-radius:8px 8px 0 0;'>
            <h2 style='color:#fff;margin:0;'>Trường THPT Hàm Thuận Nam</h2>
            <p style='color:rgba(255,255,255,0.8);margin:5px 0 0;font-size:14px;'>Xác nhận đăng ký nguyện vọng lớp 10</p>
          </div>
          <div style='background:#fff;padding:24px;border:1px solid #e0e0e0;border-radius:0 0 8px 8px;'>
            <p style='font-size:15px;'>Xin chào <strong style='color:#004080;'>{$row['ho_ten']}</strong>,</p>
            <p style='font-size:14px;color:#555;'>Bạn đã đăng ký nguyện vọng tuyển sinh lớp 10 thành công:</p>
            <table style='width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;'>
              <tr style='background:#f8f9ff;'>
                <td style='padding:10px 14px;border:1px solid #e0e0e0;font-weight:600;width:40%;'>Họ và tên</td>
                <td style='padding:10px 14px;border:1px solid #e0e0e0;'>{$row['ho_ten']}</td>
              </tr>
              <tr>
                <td style='padding:10px 14px;border:1px solid #e0e0e0;font-weight:600;'>Ngày sinh</td>
                <td style='padding:10px 14px;border:1px solid #e0e0e0;'>{$row['ngay_sinh']}</td>
              </tr>
              <tr style='background:#f8f9ff;'>
                <td style='padding:10px 14px;border:1px solid #e0e0e0;font-weight:600;'>Lớp</td>
                <td style='padding:10px 14px;border:1px solid #e0e0e0;'>{$row['lop']}</td>
              </tr>
              <tr>
                <td style='padding:10px 14px;border:1px solid #e0e0e0;font-weight:600;'>Nguyện vọng 1</td>
                <td style='padding:10px 14px;border:1px solid #e0e0e0;color:#1a6fbc;'>{$row['nv1']}</td>
              </tr>
              <tr style='background:#f8f9ff;'>
                <td style='padding:10px 14px;border:1px solid #e0e0e0;font-weight:600;'>Nguyện vọng 2</td>
                <td style='padding:10px 14px;border:1px solid #e0e0e0;color:#1a6fbc;'>{$row['nv2']}</td>
              </tr>
            </table>
            <div style='background:#fffbeb;border-left:4px solid #f59e0b;padding:12px 16px;border-radius:0 6px 6px 0;margin-top:16px;'>
              <p style='margin:0;font-size:13px;color:#92400e;'><strong>⚠ Lưu ý:</strong> Mỗi học sinh chỉ được đăng ký một lần. Nếu cần thay đổi, vui lòng liên hệ nhà trường.</p>
            </div>
            <p style='font-size:13px;color:#888;margin-top:20px;'>Trân trọng,<br><strong>Trường THPT Hàm Thuận Nam</strong></p>
          </div>
        </body></html>";

        $mail->send();

        // Đánh dấu đã gửi
        $id = (int)$row['id'];
        $conn->query("UPDATE mail_queue SET sent=1, sent_at=NOW() WHERE id=$id");
        $count++;
        sleep(1);

    } catch (Exception $e) {
        // Ghi log lỗi
        $id  = (int)$row['id'];
        $err = $conn->real_escape_string($e->getMessage());
        $conn->query("UPDATE mail_queue SET sent=2 WHERE id=$id"); // 2 = lỗi
        error_log("Queue mail error id {$id}: " . $e->getMessage());
    }
}

echo "✅ Đã gửi $count email.";
$conn->close();