<?php
// 1) Nạp Composer autoload (đã cài qua composer install) và config chung
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Kết nối CSDL và lấy dữ liệu danh sách
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

$sql = "SELECT ho_ten,so_bao_danh, nguyen_vong_1, nguyen_vong_2,ngay_dang_ky FROM hoc_sinh ORDER BY ho_ten";
$result = $conn->query($sql);

// Tạo file Excel
$spreadsheet = new Spreadsheet();
$spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(11);
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Danh sách đăng ký nguyện vọng");

// Ghi tiêu đề cột
$sheet->setCellValue('A1', 'STT');
$sheet->setCellValue('B1', 'Họ và tên');
$sheet->setCellValue('C1', 'Số báo danh');
$sheet->setCellValue('D1', 'Nguyện vọng 1');
$sheet->setCellValue('E1', 'Nguyện vọng 2');
$sheet->setCellValue('F1', 'Ngày đăng ký');

// Ghi dữ liệu từ CSDL
$row = 2;
$stt = 1;

if ($result->num_rows > 0) {
    while ($hs = $result->fetch_assoc()) {
        $sheet->setCellValue("A$row", $stt);
        $sheet->setCellValue("B$row", $hs['ho_ten']);
        $sheet->setCellValue("C$row", $hs['so_bao_danh']);
        $sheet->setCellValue("D$row", $hs['nguyen_vong_1']);
        $sheet->setCellValue("E$row", $hs['nguyen_vong_2']);
		$sheet->setCellValue("F$row", $hs['ngay_dang_ky']);
        $row++;
        $stt++;
    }
}

// Căn chỉnh tự động độ rộng cột
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Header tải file Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="danhsach_nguyenvong.xlsx"');
header('Cache-Control: max-age=0');

// Ghi file ra output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
