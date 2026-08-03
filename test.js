import http from 'k6/http';
import { sleep, check, group } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

// ── Custom metrics ──
const dangKySuccess  = new Counter('dang_ky_success');
const dangKyFail     = new Counter('dang_ky_fail');
const mailQueueAdded = new Counter('mail_queue_added');
const errorRate      = new Rate('error_rate');
const loadTime       = new Trend('load_time');

// ── Cấu hình test ──
export const options = {
  scenarios: {
    // Đợt 1: 8:00 — THCS Thuận Nam + Tân Thuận (~335 HS trong 60 phút)
    dot_1: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 20 },  // Tăng dần
        { duration: '2m',  target: 50 },  // Đỉnh điểm
        { duration: '1m',  target: 30 },  // Giảm dần
        { duration: '30s', target: 0  },
      ],
      gracefulRampDown: '10s',
      tags: { dot: '1' },
    },
    // Đợt 2: 9:30 — Thí sinh tự do + trường khác (~335 HS)
    dot_2: {
      executor: 'ramping-vus',
      startTime: '5m', // Bắt đầu sau đợt 1
      startVUs: 0,
      stages: [
        { duration: '30s', target: 30 },
        { duration: '2m',  target: 60 },
        { duration: '1m',  target: 30 },
        { duration: '30s', target: 0  },
      ],
      gracefulRampDown: '10s',
      tags: { dot: '2' },
    },
  },

  thresholds: {
    // 95% request phải hoàn thành trong 3 giây
    http_req_duration:          ['p(95)<3000'],
    // Tỉ lệ lỗi dưới 5%
    http_req_failed:            ['rate<0.05'],
    error_rate:                 ['rate<0.05'],
    // Trang chủ load dưới 2 giây
    'http_req_duration{page:index}': ['p(95)<2000'],
    // API đăng ký dưới 5 giây
    'http_req_duration{page:dangky}': ['p(95)<5000'],
  },
};

const BASE_URL = 'https://nguyenvong.thpthamthuannam.edu.vn';

// Danh sách tổ hợp môn (id từ DB)
const TO_HOP_IDS = [1, 2, 3, 4, 5];

// Tạo dữ liệu học sinh giả
function randomStudent(vu) {
  const names = [
    'Nguyễn Văn An', 'Trần Thị Bình', 'Lê Hoàng Nam',
    'Phạm Thị Hoa', 'Hoàng Văn Minh', 'Đặng Thị Lan',
    'Bùi Quốc Hùng', 'Võ Thị Mai', 'Đinh Văn Phúc',
    'Ngô Thị Thu', 'Lý Văn Đức', 'Dương Thị Ngọc'
  ];
  const lops = ['9a1', '9a2', '9a3', '9a4', '9a5', '9b1', '9b2'];

  // Dùng VU id + timestamp để tạo dữ liệu unique
  const idx  = (vu * 100 + Date.now()) % names.length;
  const nv1  = TO_HOP_IDS[vu % TO_HOP_IDS.length];
  const nv2  = TO_HOP_IDS[(vu + 1) % TO_HOP_IDS.length] === nv1
               ? TO_HOP_IDS[(vu + 2) % TO_HOP_IDS.length]
               : TO_HOP_IDS[(vu + 1) % TO_HOP_IDS.length];

  return {
    ho_ten:        names[idx],
    ngay_sinh:     `2009-0${(vu % 9) + 1}-${String((vu % 28) + 1).padStart(2, '0')}`,
    lop:           lops[vu % lops.length],
    so_dien_thoai: `09${String(vu).padStart(8, '0')}`.slice(0, 10),
    email:         `test${vu}@gmail.com`,
    nv1:           nv1,
    nv2:           nv2,
  };
}

export default function () {
  const vu = __VU; // Virtual User ID

  // ── TEST 1: Load trang chủ ──
  group('1. Trang chủ', () => {
    const res = http.get(BASE_URL, {
      tags: { page: 'index' },
    });

    const ok = check(res, {
      'Trang chủ: status 200':    (r) => r.status === 200,
      'Trang chủ: có form đăng ký': (r) => r.body.includes('form-dangky'),
      'Trang chủ: load < 3s':     (r) => r.timings.duration < 3000,
    });

    loadTime.add(res.timings.duration);
    errorRate.add(!ok);
    sleep(1);
  });

  // ── TEST 2: Load trang tra cứu ──
  group('2. Trang tra cứu', () => {
    const res = http.get(`${BASE_URL}/lookupinf.php`, {
      tags: { page: 'tracuu' },
    });

    check(res, {
      'Tra cứu: status 200':        (r) => r.status === 200,
      'Tra cứu: có form tra cứu':   (r) => r.body.includes('inp-hoten'),
    });

    sleep(1);
  });

  // ── TEST 3: API tra cứu ──
  group('3. API tra cứu', () => {
    const res = http.post(
      `${BASE_URL}/api/lookupinf.php`,
      { ho_ten: 'Nguyễn Văn Test', ngay_sinh: '15/09/2009' },
      {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        tags: { page: 'api_tracuu' },
      }
    );

    check(res, {
      'API tra cứu: status 200':      (r) => r.status === 200,
      'API tra cứu: trả về JSON':     (r) => {
        try { JSON.parse(r.body); return true; } catch { return false; }
      },
    });

    sleep(1);
  });

  // ── TEST 4: Đăng ký nguyện vọng ──
  group('4. Đăng ký nguyện vọng', () => {
    const student = randomStudent(vu);

    const res = http.post(
      `${BASE_URL}/api/dangky.php`,
      student,
      {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        tags:    { page: 'dangky' },
        redirects: 5,
      }
    );

    const ok = check(res, {
      'Đăng ký: không lỗi server':  (r) => r.status !== 500,
      'Đăng ký: redirect thành công': (r) =>
        r.status === 200 &&
        (r.url.includes('success.php') || r.url.includes('index.php')),
    });

    if (ok && res.url.includes('success.php')) {
      dangKySuccess.add(1);
      mailQueueAdded.add(1);
    } else {
      dangKyFail.add(1);
      errorRate.add(1);
    }

    sleep(2);
  });

  // ── TEST 5: Load trang 404 ──
  group('5. Trang 404', () => {
    const res = http.get(`${BASE_URL}/trang-khong-ton-tai`, {
      tags: { page: '404' },
    });

    check(res, {
      '404: hiển thị trang tùy chỉnh': (r) =>
        r.status === 404 && r.body.includes('Không tìm thấy trang'),
    });

    sleep(1);
  });
}

// ── Kết quả tổng kết ──
export function handleSummary(data) {
  const passed = data.metrics.http_req_failed?.values?.rate < 0.05;

  return {
    'stdout': `
╔══════════════════════════════════════════════════════╗
║         KẾT QUẢ TEST - THPT HÀM THUẬN NAM           ║
╠══════════════════════════════════════════════════════╣
║ Tổng request:     ${String(data.metrics.http_reqs?.values?.count ?? 0).padEnd(10)}                        ║
║ Đăng ký thành:    ${String(data.metrics.dang_ky_success?.values?.count ?? 0).padEnd(10)}                        ║
║ Đăng ký thất bại: ${String(data.metrics.dang_ky_fail?.values?.count ?? 0).padEnd(10)}                        ║
║ Thời gian TB:     ${String(Math.round(data.metrics.http_req_duration?.values?.avg ?? 0)) + 'ms'}                        ║
║ P95 load time:    ${String(Math.round(data.metrics.http_req_duration?.values['p(95)'] ?? 0)) + 'ms'}                        ║
║ Tỉ lệ lỗi:       ${String(((data.metrics.error_rate?.values?.rate ?? 0) * 100).toFixed(2)) + '%'}                        ║
║ Kết quả:          ${passed ? '✅ ĐẠT' : '❌ KHÔNG ĐẠT'}                         ║
╚══════════════════════════════════════════════════════╝
    `,
    'test_result.json': JSON.stringify(data, null, 2),
  };
}