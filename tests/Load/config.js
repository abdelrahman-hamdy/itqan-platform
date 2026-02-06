/**
 * Shared configuration for k6 load tests.
 *
 * Usage: k6 run tests/Load/smoke.js --env BASE_URL=https://itqanway.com
 */

export const BASE_URL = __ENV.BASE_URL || 'https://itqan-platform.test';
export const SUBDOMAIN = __ENV.SUBDOMAIN || 'itqan-academy';

// Thresholds for acceptable performance
export const THRESHOLDS = {
    http_req_duration: ['p(95)<2000', 'p(99)<5000'],  // 95% under 2s, 99% under 5s
    http_req_failed: ['rate<0.05'],                     // <5% error rate
    http_reqs: ['rate>10'],                             // At least 10 req/s
};

// Test user credentials (use seeded test accounts)
export const TEST_USERS = {
    student: {
        email: __ENV.STUDENT_EMAIL || 'student@test.com',
        password: __ENV.STUDENT_PASS || 'password',
    },
    teacher: {
        email: __ENV.TEACHER_EMAIL || 'teacher@test.com',
        password: __ENV.TEACHER_PASS || 'password',
    },
    admin: {
        email: __ENV.ADMIN_EMAIL || 'admin@test.com',
        password: __ENV.ADMIN_PASS || 'password',
    },
};
