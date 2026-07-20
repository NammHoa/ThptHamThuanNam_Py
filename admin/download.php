<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

$sql = "SELECT ho_ten, ngay_sinh, lop, so_dien_thoai, email, nguyen_vong_1, nguyen_vong_2
        FROM hoc_sinh ORDER BY lop ASC, ho_ten ASC";
$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(11);
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Danh sách đăng ký nguyện vọng");

$headers = ['STT', 'Họ và tên', 'Ngày sinh', 'Lớp', 'SĐT phụ huynh', 'Email', 'Nguyện vọng 1', 'Nguyện vọng 2'];
$cols    = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

foreach ($headers as $i => $h) {
    $sheet->setCellValue($cols[$i] . '1', $h);
}

$headerStyle = [
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004080']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
];
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

$row = 2;
$stt = 1;
if ($result && $result->num_rows > 0) {
    while ($hs = $result->fetch_assoc()) {
        $sheet->setCellValue("A$row", $stt);
        $sheet->setCellValue("B$row", $hs['ho_ten']);
        $sheet->setCellValue("C$row", $hs['ngay_sinh']);
        $sheet->setCellValue("D$row", $hs['lop']);
        $sheet->setCellValue("E$row", $hs['so_dien_thoai'] ?? '');
        $sheet->setCellValue("F$row", $hs['email']);
        $sheet->setCellValue("G$row", $hs['nguyen_vong_1']);
        $sheet->setCellValue("H$row", $hs['nguyen_vong_2']);
        $row++;
        $stt++;
    }
}

foreach ($cols as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$filename = 'danhsach_nguyenvong_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;