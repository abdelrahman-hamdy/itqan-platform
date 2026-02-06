import http from 'k6/http';
import { check, sleep } from 'k6';
import { BASE_URL, THRESHOLDS } from './config.js';

/**
 * Smoke test - Quick sanity check that key endpoints respond.
 *
 * Run: k6 run tests/Load/smoke.js
 * Run against prod: k6 run tests/Load/smoke.js --env BASE_URL=https://itqanway.com
 */
export const options = {
    vus: 5,
    duration: '30s',
    thresholds: THRESHOLDS,
};

export default function () {
    // Public pages
    const pages = [
        { url: `${BASE_URL}/`, name: 'homepage' },
        { url: `${BASE_URL}/login`, name: 'login_page' },
    ];

    for (const page of pages) {
        const res = http.get(page.url, {
            tags: { name: page.name },
        });

        check(res, {
            [`${page.name} status 200`]: (r) => r.status === 200,
            [`${page.name} under 2s`]: (r) => r.timings.duration < 2000,
        });
    }

    // Health check endpoint
    const healthRes = http.get(`${BASE_URL}/health`, {
        tags: { name: 'health_check' },
    });

    check(healthRes, {
        'health check responds': (r) => r.status === 200 || r.status === 302,
    });

    sleep(1);
}
