import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';
import { BASE_URL, TEST_USERS, THRESHOLDS } from './config.js';
import { login, checkPage } from './helpers.js';

/**
 * Load test - Simulate 50K MAU traffic patterns.
 *
 * 50K MAU ≈ ~1,700 daily active users ≈ ~100-200 concurrent during peak.
 * We ramp up to 200 VUs to simulate peak traffic.
 *
 * Run: k6 run tests/Load/load.js
 * Run against prod: k6 run tests/Load/load.js --env BASE_URL=https://itqanway.com
 */

// Custom metrics
const loginDuration = new Trend('login_duration');
const dashboardDuration = new Trend('dashboard_duration');
const pageErrors = new Counter('page_errors');

export const options = {
    stages: [
        { duration: '1m', target: 50 },    // Ramp up to 50 users
        { duration: '3m', target: 100 },   // Ramp to 100 (normal load)
        { duration: '5m', target: 200 },   // Peak load: 200 concurrent
        { duration: '3m', target: 200 },   // Sustain peak
        { duration: '2m', target: 50 },    // Cool down
        { duration: '1m', target: 0 },     // Ramp down
    ],
    thresholds: {
        ...THRESHOLDS,
        login_duration: ['p(95)<3000'],
        dashboard_duration: ['p(95)<4000'],
        page_errors: ['count<50'],
    },
};

export function setup() {
    // Warm up - verify the app is reachable
    const res = http.get(`${BASE_URL}/login`);
    check(res, { 'setup: app is reachable': (r) => r.status === 200 });
}

export default function () {
    // Simulate realistic user journey mix:
    // 60% students, 25% teachers, 15% admins
    const rand = Math.random();

    if (rand < 0.60) {
        studentJourney();
    } else if (rand < 0.85) {
        teacherJourney();
    } else {
        adminJourney();
    }
}

function studentJourney() {
    group('Student Journey', function () {
        // 1. Visit login page
        const loginPage = http.get(`${BASE_URL}/login`, {
            tags: { name: 'GET /login' },
        });
        check(loginPage, { 'login page loaded': (r) => r.status === 200 });
        sleep(1);

        // 2. Submit login
        const csrfMatch = loginPage.body.match(/name="_token"\s+value="([^"]+)"/);
        const csrf = csrfMatch ? csrfMatch[1] : '';

        const start = Date.now();
        const loginRes = http.post(`${BASE_URL}/login`, {
            _token: csrf,
            email: TEST_USERS.student.email,
            password: TEST_USERS.student.password,
        }, {
            redirects: 5,
            tags: { name: 'POST /login' },
        });
        loginDuration.add(Date.now() - start);

        if (loginRes.status !== 200) {
            pageErrors.add(1);
            return;
        }

        sleep(2);

        // 3. Browse student pages
        const studentPages = [
            '/student/dashboard',
            '/student/calendar',
            '/student/subscriptions',
        ];

        for (const page of studentPages) {
            const dashStart = Date.now();
            const res = http.get(`${BASE_URL}${page}`, {
                tags: { name: `GET ${page}` },
            });
            dashboardDuration.add(Date.now() - dashStart);

            check(res, {
                [`${page} accessible`]: (r) => r.status === 200 || r.status === 302,
            });

            if (res.status >= 500) {
                pageErrors.add(1);
            }

            sleep(Math.random() * 3 + 1); // 1-4s between pages (realistic browsing)
        }

        // 4. Logout
        http.post(`${BASE_URL}/logout`, { _token: csrf }, {
            redirects: 5,
            tags: { name: 'POST /logout' },
        });

        sleep(1);
    });
}

function teacherJourney() {
    group('Teacher Journey', function () {
        // Teacher loads Filament panel pages
        const loginPage = http.get(`${BASE_URL}/login`, {
            tags: { name: 'GET /login' },
        });
        check(loginPage, { 'login page loaded': (r) => r.status === 200 });

        const csrfMatch = loginPage.body.match(/name="_token"\s+value="([^"]+)"/);
        const csrf = csrfMatch ? csrfMatch[1] : '';

        http.post(`${BASE_URL}/login`, {
            _token: csrf,
            email: TEST_USERS.teacher.email,
            password: TEST_USERS.teacher.password,
        }, {
            redirects: 5,
            tags: { name: 'POST /login (teacher)' },
        });

        sleep(2);

        // Teacher panel pages
        const teacherPages = [
            '/teacher',
            '/teacher/quran-sessions',
        ];

        for (const page of teacherPages) {
            const res = http.get(`${BASE_URL}${page}`, {
                tags: { name: `GET ${page}` },
            });

            check(res, {
                [`${page} accessible`]: (r) => r.status === 200 || r.status === 302,
            });

            if (res.status >= 500) {
                pageErrors.add(1);
            }

            sleep(Math.random() * 3 + 2);
        }

        http.post(`${BASE_URL}/logout`, { _token: csrf }, {
            redirects: 5,
            tags: { name: 'POST /logout' },
        });

        sleep(1);
    });
}

function adminJourney() {
    group('Admin Journey', function () {
        // Admin loads Filament admin panel
        const loginPage = http.get(`${BASE_URL}/login`, {
            tags: { name: 'GET /login' },
        });

        const csrfMatch = loginPage.body.match(/name="_token"\s+value="([^"]+)"/);
        const csrf = csrfMatch ? csrfMatch[1] : '';

        http.post(`${BASE_URL}/login`, {
            _token: csrf,
            email: TEST_USERS.admin.email,
            password: TEST_USERS.admin.password,
        }, {
            redirects: 5,
            tags: { name: 'POST /login (admin)' },
        });

        sleep(2);

        // Admin panel pages (heavy queries)
        const adminPages = [
            '/academy',
            '/academy/quran-sessions',
            '/academy/quran-subscriptions',
            '/academy/payments',
        ];

        for (const page of adminPages) {
            const res = http.get(`${BASE_URL}${page}`, {
                tags: { name: `GET ${page}` },
            });

            check(res, {
                [`${page} accessible`]: (r) => r.status === 200 || r.status === 302,
            });

            if (res.status >= 500) {
                pageErrors.add(1);
            }

            sleep(Math.random() * 4 + 2);
        }

        http.post(`${BASE_URL}/logout`, { _token: csrf }, {
            redirects: 5,
            tags: { name: 'POST /logout' },
        });

        sleep(1);
    });
}
