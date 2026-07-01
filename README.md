# 📋 Hệ Thống Đăng Ký Nguyện Vọng Lớp 10
### THPT Hàm Thuận Nam – Năm học 2025–2026

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat)
![Status](https://img.shields.io/badge/Status-Active-brightgreen?style=flat)

> Online registration system for Grade 10 enrollment preferences at THPT Ham Thuan Nam High School, Lam Dong. Built with PHP, MySQL, and PhpSpreadsheet.

🌐 **Live:** [nguyenvong.thpthamthuannam.edu.vn](https://nguyenvong.thpthamthuannam.edu.vn)

---

## ✨ Tính năng

### Học sinh
- Đăng ký nguyện vọng tuyển sinh lớp 10 trực tuyến
- Xác thực số báo danh + họ tên với danh sách trúng tuyển
- Tra cứu kết quả đăng ký theo SBD
- Hiển thị đếm ngược thời hạn đăng ký
- Nhận email xác nhận sau khi đăng ký thành công
- Trang xác nhận với đầy đủ thông tin nguyện vọng

### Quản trị viên
- Dashboard thống kê số lượng đăng ký theo tổ hợp môn
- Import danh sách trúng tuyển từ file Excel (.xlsx)
- Export danh sách đăng ký ra file Excel
- Quản lý tổ hợp môn (thêm, sửa, xóa)
- Cài đặt thời hạn đăng ký linh hoạt
- Tìm kiếm, phân trang danh sách học sinh
- Sửa/xóa thông tin đăng ký

---

## 🛠️ Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Backend | PHP 8.1+ |
| Database | MySQL 8.0+ |
| Frontend | HTML, CSS, JavaScript |
| Excel | PhpSpreadsheet 4.x |
| Icons | Tabler Icons |
| Charts | Chart.js |
| Server | Apache / Nginx |

---

## 📁 Cấu trúc dự án
├── admin/
│   ├── dashboard.php         # Trang quản trị chính
│   ├── login.php             # Đăng nhập admin
│   ├── logout.php            # Đăng xuất
│   ├── import_trungtuyen.php # Import danh sách trúng tuyển
│   ├── download.php          # Export danh sách đăng ký
│   ├── deadline.php          # Cài đặt thời hạn
│   └── tohop.php             # Quản lý tổ hợp môn
├── api/
│   ├── dangky.php            # API xử lý đăng ký
│   └── lookupinf.php         # API tra cứu SBD
├── db/
│   └── nguyenvong.sql        # Schema database
├── images/                   # Logo, favicon
├── vendor/                   # Composer packages
├── index.php                 # Trang đăng ký chính
├── success.php               # Trang xác nhận thành công
├── lookupinf.php             # Trang tra cứu
├── style.css                 # Stylesheet chính
├── autoload.php              # Custom autoloader
├── config.php                # Cấu hình DB
└── loading.php               # Loading overlay
---

## ⚙️ Cài đặt

### Yêu cầu
- PHP >= 8.1
- MySQL >= 8.0
- Composer
- Apache/Nginx với mod_rewrite

### Các bước cài đặt

**1. Clone repository**
```bash
git clone https://github.com/NammHoa/ThptHamThuanNam_Py.git
cd ThptHamThuanNam_Py
```

**2. Cài đặt dependencies**
```bash
composer install
```

**3. Tạo database**
```sql
mysql -u root -p < db/nguyenvong.sql
```

**4. Cấu hình kết nối**

Mở file `config.php` và chỉnh sửa:
```php
```

**5. Hash mật khẩu admin**

Upload `hash_password_tool.php` lên server, truy cập để tạo mật khẩu mới, sau đó cập nhật vào DB:
```sql
UPDATE admin_users SET password = 'bcrypt_hash_here' WHERE username = 'admin';
```
> ⚠️ Xóa file `hash_password_tool.php` ngay sau khi dùng!

---

## 🔐 Bảo mật

- Mật khẩu admin được hash bằng **BCrypt** (`password_hash`)
- Toàn bộ form admin có **CSRF Token**
- Giới hạn số lần đăng nhập sai (chống **Brute Force**)
- Session regenerate sau khi đăng nhập
- Prepared statements chống **SQL Injection**
- `htmlspecialchars()` chống **XSS**

---

## 📊 Database Schema

| Bảng | Mô tả |
|---|---|
| `hoc_sinh` | Danh sách học sinh đã đăng ký nguyện vọng |
| `danh_sach_trung_tuyen` | Danh sách học sinh trúng tuyển (import từ Excel) |
| `to_hop` | Danh sách tổ hợp môn |
| `thietlap` | Cấu hình hệ thống (hạn đăng ký) |
| `admin_users` | Tài khoản quản trị viên |

---

## 📸 Screenshots

| Trang đăng ký | Trang xác nhận | Admin Dashboard |
|---|---|---|
| Đăng ký nguyện vọng | Kết quả đăng ký | Thống kê & quản lý |

---

## 👨‍💻 Tác giả
- Trường THPT Hàm Thuận Nam, Tỉnh Lâm Đồng
- 📧 [c3hamthuannam.binhthuan@moet.edu.vn](mailto:c3hamthuannam.binhthuan@moet.edu.vn)
- 🌐 [thpthamthuannam.edu.vn](http://thpthamthuannam.edu.vn)

---

## 📄 License

This project is licensed under the MIT License.

---

<p align="center">
  Made with ❤️ for THPT Hàm Thuận Nam
</p>
