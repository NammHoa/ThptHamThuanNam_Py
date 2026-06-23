import http from 'k6/http';
import { sleep, check } from 'k6';

export const options = {
  stages: [
    { duration: '1m', target: 50 },
    { duration: '2m', target: 100 },
    { duration: '1m', target: 200 },
    { duration: '1m', target: 300 },
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<3000'],
    http_req_failed: ['rate<0.05'],
  },
};

export default function () {
  const res = http.get('https://nguyenvong.thpthamthuannam.edu.vn');
  check(res, {
    'status 200': (r) => r.status === 200,
    'load time < 3s': (r) => r.timings.duration < 3000,
  });
  sleep(2);
}