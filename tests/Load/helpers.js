import http from 'k6/http';
import { check } from 'k6';
import { BASE_URL } from './config.js';

/**
 * Login and return session cookies for authenticated requests.
 */
export function login(email, password) {
    // Get CSRF token from login page
    const loginPage = http.get(`${BASE_URL}/login`);
    const csrfMatch = loginPage.body.match(/name="_token"\s+value="([^"]+)"/);
    const csrf = csrfMatch ? csrfMatch[1] : '';

    const res = http.post(`${BASE_URL}/login`, {
        _token: csrf,
        email: email,
        password: password,
    }, {
        redirects: 0,
    });

    check(res, {
        'login successful': (r) => r.status === 302,
    });

    return http.cookieJar();
}

/**
 * Make an authenticated GET request.
 */
export function authGet(url, jar) {
    return http.get(url, {
        jar: jar,
        tags: { name: url.replace(BASE_URL, '') },
    });
}

/**
 * Check standard page response.
 */
export function checkPage(res, name) {
    check(res, {
        [`${name} status 200`]: (r) => r.status === 200,
        [`${name} has content`]: (r) => r.body && r.body.length > 0,
        [`${name} under 3s`]: (r) => r.timings.duration < 3000,
    });
}
