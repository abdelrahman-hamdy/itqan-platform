import { test, expect } from '@playwright/test';
import { getApiClient, getApiToken, getPublicClient, assertSuccessResponse, assertErrorResponse, assertPaginatedResponse, assertHasData, assertStatusOneOf, delay, API_ACCOUNTS, API_BASE } from '../fixtures/api.fixture';
import axios, { AxiosInstance } from 'axios';

test.describe('API - Validation & Error Handling', () => {

  // ─── Unauthenticated Access ──────────────────────────────────────────

  test('unauthenticated request returns 401', async () => {
    const client = getPublicClient();
    const res = await client.get('/student/dashboard');
    assertErrorResponse(res, 401);
  });

  test('invalid token returns 401', async () => {
    const res = await axios.get(`${API_BASE}/student/dashboard`, {
      headers: {
        'Authorization': 'Bearer invalid-token-abc123',
        'Accept': 'application/json',
        'X-Academy-Subdomain': 'e2e-test',
      },
      validateStatus: () => true,
    });
    assertErrorResponse(res, 401);
  });

  test('expired-format token returns 401', async () => {
    const res = await axios.get(`${API_BASE}/me`, {
      headers: {
        'Authorization': 'Bearer 999|totally-fake-token-value',
        'Accept': 'application/json',
        'X-Academy-Subdomain': 'e2e-test',
      },
      validateStatus: () => true,
    });
    assertErrorResponse(res, 401);
  });

  test('missing Authorization header returns 401', async () => {
    const res = await axios.get(`${API_BASE}/student/dashboard`, {
      headers: {
        'Accept': 'application/json',
        'X-Academy-Subdomain': 'e2e-test',
      },
      validateStatus: () => true,
    });
    assertErrorResponse(res, 401);
  });

  // ─── Role-Based Access Control ───────────────────────────────────────

  test('student cannot access teacher endpoints', async () => {
    const client = await getApiClient('student');
    const res = await client.get('/teacher/dashboard');
    assertStatusOneOf(res, [401, 403]);
  });

  test('teacher cannot access student endpoints', async () => {
    const client = await getApiClient('teacher');
    const res = await client.get('/student/dashboard');
    assertStatusOneOf(res, [401, 403]);
  });

  test('parent cannot access admin endpoints', async () => {
    const client = await getApiClient('parent');
    const res = await client.get('/admin/sessions');
    assertStatusOneOf(res, [401, 403]);
  });

  test('student cannot access admin endpoints', async () => {
    const client = await getApiClient('student');
    const res = await client.get('/admin/sessions');
    assertStatusOneOf(res, [401, 403]);
  });

  test('student cannot access parent endpoints', async () => {
    const client = await getApiClient('student');
    const res = await client.get('/parent/dashboard');
    assertStatusOneOf(res, [401, 403]);
  });

  test('teacher cannot access supervisor endpoints', async () => {
    const client = await getApiClient('teacher');
    const res = await client.get('/supervisor/chat/supervised-groups');
    assertStatusOneOf(res, [401, 403]);
  });

  // ─── Academy Subdomain Validation ────────────────────────────────────

  test('invalid academy subdomain returns 404', async () => {
    const res = await axios.get(`${API_BASE}/academy/branding`, {
      headers: {
        'Accept': 'application/json',
        'X-Academy-Subdomain': 'nonexistent-academy-xyz',
      },
      validateStatus: () => true,
    });
    expect(res.status).toBe(404);
  });

  // ─── Login Validation ────────────────────────────────────────────────

  test('POST /login with empty body returns 422', async () => {
    const client = getPublicClient();
    const res = await client.post('/login', {});
    // Accept 422 or 429 (rate limited)
    assertStatusOneOf(res, [422, 429]);
    if (res.status === 422) assertErrorResponse(res, 422);
  });

  test('POST /login with invalid email format returns 422', async () => {
    const client = getPublicClient();
    const res = await client.post('/login', {
      email: 'not-an-email',
      password: 'SomePassword123',
      device_name: 'e2e-test',
    });
    // Accept 422 or 429 (rate limited)
    assertStatusOneOf(res, [422, 429]);
    if (res.status === 422) assertErrorResponse(res, 422);
  });

  test('POST /login with missing password returns 422', async () => {
    const client = getPublicClient();
    const res = await client.post('/login', {
      email: API_ACCOUNTS.student.email,
      device_name: 'e2e-test',
    });
    // Accept 422 or 429 (rate limited)
    assertStatusOneOf(res, [422, 429]);
    if (res.status === 422) assertErrorResponse(res, 422);
  });

  test('POST /forgot-password with nonexistent email returns 422 or error', async () => {
    const client = getPublicClient();
    const res = await client.post('/forgot-password', {
      email: 'nonexistent-user-e2e@example.com',
    });
    // Should return 422 (validation), 200 (security), or 429 (rate limited)
    assertStatusOneOf(res, [200, 422, 429]);
  });
});
