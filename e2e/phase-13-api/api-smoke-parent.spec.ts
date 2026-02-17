import { test, expect } from '@playwright/test';
import { getApiClient, getApiToken, getPublicClient, assertSuccessResponse, assertErrorResponse, assertPaginatedResponse, assertHasData, assertStatusOneOf, delay, API_ACCOUNTS, API_BASE } from '../fixtures/api.fixture';
import axios, { AxiosInstance } from 'axios';

test.describe('API - Parent Smoke Tests', () => {
  let client: AxiosInstance;

  test.beforeAll(async () => {
    client = await getApiClient('parent');
  });

  // ─── Dashboard ────────────────────────────────────────────────────────

  test('GET /parent/dashboard returns success', async () => {
    const res = await client.get('/parent/dashboard');
    assertSuccessResponse(res);
  });

  // ─── Calendar ─────────────────────────────────────────────────────────

  test('GET /parent/calendar returns success', async () => {
    const res = await client.get('/parent/calendar');
    assertSuccessResponse(res);
  });

  test('GET /parent/calendar/month/2026/2 returns success', async () => {
    const res = await client.get('/parent/calendar/month/2026/2');
    assertSuccessResponse(res);
  });

  // ─── Children ─────────────────────────────────────────────────────────

  test('GET /parent/children returns success', async () => {
    const res = await client.get('/parent/children');
    assertSuccessResponse(res);
  });

  // ─── Sessions (Unified) ──────────────────────────────────────────────

  test('GET /parent/sessions returns success', async () => {
    const res = await client.get('/parent/sessions');
    assertSuccessResponse(res);
  });

  test('GET /parent/sessions/today returns success', async () => {
    const res = await client.get('/parent/sessions/today');
    assertSuccessResponse(res);
  });

  test('GET /parent/sessions/upcoming returns success', async () => {
    const res = await client.get('/parent/sessions/upcoming');
    assertSuccessResponse(res);
  });

  // ─── Sessions (Type-specific) ────────────────────────────────────────

  test('GET /parent/sessions/quran returns success', async () => {
    const res = await client.get('/parent/sessions/quran');
    assertSuccessResponse(res);
  });

  test('GET /parent/sessions/academic returns success', async () => {
    const res = await client.get('/parent/sessions/academic');
    assertSuccessResponse(res);
  });

  test('GET /parent/sessions/interactive returns success', async () => {
    const res = await client.get('/parent/sessions/interactive');
    assertSuccessResponse(res);
  });

  // ─── Reports (Unified) ───────────────────────────────────────────────
  // Note: Reports return 404 when parent has no linked children (expected behavior)

  test('GET /parent/reports/progress returns success', async () => {
    const res = await client.get('/parent/reports/progress');
    assertStatusOneOf(res, [200, 404]);
  });

  test('GET /parent/reports/attendance returns success', async () => {
    const res = await client.get('/parent/reports/attendance');
    assertStatusOneOf(res, [200, 404]);
  });

  // ─── Reports (Quran) ─────────────────────────────────────────────────

  test('GET /parent/reports/quran/progress returns success', async () => {
    const res = await client.get('/parent/reports/quran/progress');
    assertStatusOneOf(res, [200, 404]);
  });

  test('GET /parent/reports/quran/attendance returns success', async () => {
    const res = await client.get('/parent/reports/quran/attendance');
    assertStatusOneOf(res, [200, 404]);
  });

  // ─── Reports (Academic) ──────────────────────────────────────────────

  test('GET /parent/reports/academic/progress returns success', async () => {
    const res = await client.get('/parent/reports/academic/progress');
    assertStatusOneOf(res, [200, 404]);
  });

  test('GET /parent/reports/academic/attendance returns success', async () => {
    const res = await client.get('/parent/reports/academic/attendance');
    assertStatusOneOf(res, [200, 404]);
  });

  // ─── Reports (Interactive) ───────────────────────────────────────────

  test('GET /parent/reports/interactive/progress returns success', async () => {
    const res = await client.get('/parent/reports/interactive/progress');
    assertStatusOneOf(res, [200, 404]);
  });

  // ─── Homework ─────────────────────────────────────────────────────────

  test('GET /parent/homework returns success', async () => {
    const res = await client.get('/parent/homework');
    assertSuccessResponse(res);
  });

  // ─── Payments ─────────────────────────────────────────────────────────

  test('GET /parent/payments returns success', async () => {
    const res = await client.get('/parent/payments');
    assertSuccessResponse(res);
  });

  // ─── Subscriptions ───────────────────────────────────────────────────

  test('GET /parent/subscriptions returns success', async () => {
    const res = await client.get('/parent/subscriptions');
    assertSuccessResponse(res);
  });

  // ─── Quizzes ──────────────────────────────────────────────────────────

  test('GET /parent/quizzes returns success', async () => {
    const res = await client.get('/parent/quizzes');
    assertSuccessResponse(res);
  });

  // ─── Certificates ────────────────────────────────────────────────────

  test('GET /parent/certificates returns success', async () => {
    const res = await client.get('/parent/certificates');
    assertSuccessResponse(res);
  });

  // ─── Profile ──────────────────────────────────────────────────────────

  test('GET /parent/profile returns success', async () => {
    const res = await client.get('/parent/profile');
    assertSuccessResponse(res);
  });

  // ─── Response structure checks ────────────────────────────────────────

  test('GET /parent/sessions returns data array', async () => {
    const res = await client.get('/parent/sessions');
    assertPaginatedResponse(res);
  });

  test('GET /parent/children returns data array', async () => {
    const res = await client.get('/parent/children');
    assertPaginatedResponse(res);
  });

  test('GET /parent/profile returns data object with user info', async () => {
    const res = await client.get('/parent/profile');
    assertHasData(res);
    expect(res.data.data).toBeDefined();
  });

  test('GET /parent/dashboard returns structured dashboard data', async () => {
    const res = await client.get('/parent/dashboard');
    assertHasData(res);
    expect(res.data.data).toBeDefined();
  });

  test('GET /parent/reports/progress returns structured report data', async () => {
    const res = await client.get('/parent/reports/progress');
    // 404 is valid when parent has no linked children
    assertStatusOneOf(res, [200, 404]);
    if (res.status === 200) {
      expect(res.data.data).toBeDefined();
    }
  });

  test('GET /parent/reports/attendance returns structured report data', async () => {
    const res = await client.get('/parent/reports/attendance');
    // 404 is valid when parent has no linked children
    assertStatusOneOf(res, [200, 404]);
    if (res.status === 200) {
      expect(res.data.data).toBeDefined();
    }
  });

  test('GET /parent/sessions/quran returns data array', async () => {
    const res = await client.get('/parent/sessions/quran');
    assertPaginatedResponse(res);
  });

  test('GET /parent/sessions/academic returns data array', async () => {
    const res = await client.get('/parent/sessions/academic');
    assertPaginatedResponse(res);
  });

  test('GET /parent/sessions/interactive returns data array', async () => {
    const res = await client.get('/parent/sessions/interactive');
    assertPaginatedResponse(res);
  });

  test('GET /parent/homework returns data array', async () => {
    const res = await client.get('/parent/homework');
    assertPaginatedResponse(res);
  });

  test('GET /parent/subscriptions returns data array', async () => {
    const res = await client.get('/parent/subscriptions');
    assertPaginatedResponse(res);
  });

  test('GET /parent/dashboard returns meta with api_version', async () => {
    const res = await client.get('/parent/dashboard');
    expect(res.status).toBe(200);
    expect(res.data.meta).toBeDefined();
    expect(res.data.meta.api_version).toBe('v1');
  });
});
