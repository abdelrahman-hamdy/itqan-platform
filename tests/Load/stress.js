import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter } from 'k6/metrics';
import { BASE_URL, THRESHOLDS } from './config.js';

/**
 * Stress test - Find breaking points by ramping beyond expected capacity.
 *
 * WARNING: Only run against staging/test environments, never production.
 *
 * Run: k6 run tests/Load/stress.js --env BASE_URL=https://staging.itqanway.com
 */

const errors = new Counter('errors');

export const options = {
    stages: [
        { duration: '2m', target: 100 },   // Below normal load
        { duration: '3m', target: 300 },   // Normal peak
        { duration: '3m', target: 500 },   // Beyond peak (stress)
        { duration: '3m', target: 700 },   // Breaking point search
        { duration: '2m', target: 1000 },  // Maximum stress
        { duration: '5m', target: 0 },     // Recovery
    ],
    thresholds: {
        http_req_duration: ['p(95)<5000'],  // Relaxed: 5s at p95
        http_req_failed: ['rate<0.15'],     // Allow up to 15% errors under stress
        errors: ['count<500'],
    },
};

export default function () {
    // Mix of lightweight and heavyweight requests
    const endpoints = [
        { url: `${BASE_URL}/`, weight: 30 },
        { url: `${BASE_URL}/login`, weight: 25 },
        { url: `${BASE_URL}/api/health`, weight: 20 },
        { url: `${BASE_URL}/student/dashboard`, weight: 15 },
        { url: `${BASE_URL}/academy`, weight: 10 },
    ];

    // Weighted random selection
    const totalWeight = endpoints.reduce((sum, e) => sum + e.weight, 0);
    let rand = Math.random() * totalWeight;

    for (const endpoint of endpoints) {
        rand -= endpoint.weight;
        if (rand <= 0) {
            const res = http.get(endpoint.url, {
                tags: { name: endpoint.url.replace(BASE_URL, '') || '/' },
                timeout: '10s',
            });

            const ok = check(res, {
                'status is not 5xx': (r) => r.status < 500,
                'response time < 10s': (r) => r.timings.duration < 10000,
            });

            if (!ok) {
                errors.add(1);
            }

            break;
        }
    }

    sleep(Math.random() * 2 + 0.5);
}
