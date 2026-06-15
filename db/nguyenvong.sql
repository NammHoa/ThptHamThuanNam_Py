-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.41 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.6.0.6765
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table nguyenvong.admin_users
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT '',
  `password` varchar(255) COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- Dumping data for table nguyenvong.admin_users: ~1 rows (approximately)
INSERT INTO `admin_users` (`id`, `username`, `password`) VALUES
	(1, 'admin', 'password123');

-- Dumping structure for table nguyenvong.danh_sach_trung_tuyen
DROP TABLE IF EXISTS `danh_sach_trung_tuyen`;
CREATE TABLE IF NOT EXISTS `danh_sach_trung_tuyen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `so_bao_danh` varchar(50) COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT '',
  `ho_ten` varchar(100) COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `so_bao_danh` (`so_bao_danh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- Dumping data for table nguyenvong.danh_sach_trung_tuyen: ~0 rows (approximately)

-- Dumping structure for table nguyenvong.hoc_sinh
DROP TABLE IF EXISTS `hoc_sinh`;
CREATE TABLE IF NOT EXISTS `hoc_sinh` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ho_ten` varchar(100) COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT '',
  `lop` varchar(20) COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT '',
  `so_bao_danh` varchar(20) COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT '',
  ` so_dien_thoai` varchar(20) COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT '',
  `email` varchar(100) COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT '',
  `nguyen_vong_1` varchar(100) COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT '',
  `nguyen_vong_2` varchar(100) COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT '',
  `ngay_dang_ky` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `so_bao_danh` (`so_bao_danh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- Dumping data for table nguyenvong.hoc_sinh: ~0 rows (approximately)

-- Dumping structure for table nguyenvong.to_hop
DROP TABLE IF EXISTS `to_hop`;
CREATE TABLE IF NOT EXISTS `to_hop` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ten_to_hop` varchar(100) COLLATE utf8mb4_vietnamese_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- Dumping data for table nguyenvong.to_hop: ~5 rows (approximately)
INSERT INTO `to_hop` (`id`, `ten_to_hop`) VALUES
	(1, 'Vật lí, Hóa học, Sinh học, Tin học'),
	(2, 'Vật lí, Hóa học, Sinh học, Địa lí'),
	(3, 'Hóa, Sinh; KT&PL, Tin học'),
	(4, 'Địa lí, KT&PL; Vật lí, Công nghệ kỹ thuật.'),
	(5, 'Địa lí, KT&Pl; Tin học, Công nghệ trồng trọt');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
