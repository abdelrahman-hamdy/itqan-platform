import { test, expect } from '@playwright/test';
import { getApiClient, getApiToken, getPublicClient, assertSuccessResponse, assertErrorResponse, assertPaginatedResponse, assertHasData, assertStatusOneOf, delay, API_ACCOUNTS, API_BASE, ACADEMY_SUBDOMAIN } from '../fixtures/api.fixture';
import axios, { AxiosInstance } from 'axios';

test.describe('API - Authentication', () => {
  test.describe.configure({ mode: 'serial' });

  // Pre-cache tokens to avoid rate limiting during tests
  test.beforeAll(async () => {
    try { await getApiToken('student'); } catch {}
    await delay(2000);
    try { await getApiToken('supervisor'); } catch {}
  });

  test('POST /login with valid credentials returns token', { timeout: 90_000 }, async () => {
    // Use getApiToken which handles rate limit retries
    const token = await getApiToken('student');
    expect(typeof token).toBe('string');
    expect(token.length).toBeGreaterThan(0);
  });

  test('POST /login with invalid credentials returns 401', async () => {
    const client = getPublicClient();
    const res = await client.post('/login', {
      email: 'wrong@example.com',
      password: 'WrongPassword123',
      device_name: 'e2e-test',
    });
    // Accept 401 or 429 (rate limited)
    assertStatusOneOf(res, [401, 429]);
    if (res.status === 401) {
      expect(res.data.success).toBe(false);
    }
  });

  test('POST /login with missing fields returns 422', async () => {
    const client = getPublicClient();
    const res = await client.post('/login', {
      email: API_ACCOUNTS.student.email,
      // missing password and device_name
    });
    // Accept 422 or 429 (rate limited)
    assertStatusOneOf(res, [422, 429]);
    if (res.status === 422) {
      expect(res.data.success).toBe(false);
    }
  });

  test('GET /me returns authenticated user', async () => {
    const client = await getApiClient('student');
    const res = await client.get('/me');
    assertSuccessResponse(res);
    expect(res.data.data).toBeDefined();
    expect(res.data.data.user.email).toBe(API_ACCOUNTS.student.email);
  });

  test('GET /token/validate confirms valid token', async () => {
    const client = await getApiClient('student');
    const res = await client.get('/token/validate');
    expect(res.status).toBe(200);
    expect(res.data.success).toBe(true);
  });

  test('POST /token/refresh returns new token', async () => {
    const client = await getApiClient('student');
    const res = await client.post('/token/refresh');
    expect(res.status).toBe(200);
    expect(res.data.success).toBe(true);
    expect(res.data.data.token).toBeDefined();
    expect(typeof res.data.data.token).toBe('string');
  });

  test('GET /academy/branding works without auth', async () => {
    const client = getPublicClient();
    const res = await client.get('/academy/branding');
    expect(res.status).toBe(200);
    expect(res.data.success).toBe(true);
    expect(res.data.data).toBeDefined();
  });

  test('GET /server-time returns timestamp', async () => {
    const client = getPublicClient();
    const res = await client.get('/server-time');
    expect(res.status).toBe(200);
    expect(res.data.success).toBe(true);
    expect(res.data.data.timestamp).toBeDefined();
    expect(res.data.data.unix_timestamp).toBeDefined();
    expect(res.data.data.timezone).toBeDefined();
  });

  test('GET /register/subjects returns available subjects', async () => {
    const client = getPublicClient();
    const res = await client.get('/register/subjects');
    // May require academy registration to be enabled; accept 200 or 403
    assertStatusOneOf(res, [200, 403]);
    if (res.status === 200) {
      expect(res.data.success).toBe(true);
    }
  });

  test('GET /register/grade-levels returns grade levels', async () => {
    const client = getPublicClient();
    const res = await client.get('/register/grade-levels');
    // May require academy registration to be enabled; accept 200 or 403
    assertStatusOneOf(res, [200, 403]);
    if (res.status === 200) {
      expect(res.data.success).toBe(true);
    }
  });

  test('POST /forgot-password with invalid email returns 422', async () => {
    const client = getPublicClient();
    const res = await client.post('/forgot-password', {
      email: 'not-a-valid-email',
    });
    // Accept 422 or 429 (rate limited)
    assertStatusOneOf(res, [422, 429]);
    if (res.status === 422) {
      expect(res.data.success).toBe(false);
    }
  });

  test('POST /logout revokes token', { timeout: 90_000 }, async () => {
    // Get a fresh token using the supervisor role (avoids invalidating cached student/teacher tokens)
    const token = await getApiToken('supervisor');

    // Logout using the token
    const logoutRes = await axios.post(`${API_BASE}/logout`, {}, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Academy-Subdomain': ACADEMY_SUBDOMAIN,
      },
      validateStatus: () => true,
    });
    expect(logoutRes.status).toBe(200);
    expect(logoutRes.data.success).toBe(true);

    // Verify the token is now invalid
    const verifyRes = await axios.get(`${API_BASE}/me`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'X-Academy-Subdomain': ACADEMY_SUBDOMAIN,
      },
      validateStatus: () => true,
    });
    expect(verifyRes.status).toBe(401);
  });
});
