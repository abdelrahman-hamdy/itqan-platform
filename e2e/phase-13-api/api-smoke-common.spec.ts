import { test, expect } from '@playwright/test';
import { getApiClient, getApiToken, getPublicClient, assertSuccessResponse, assertErrorResponse, assertPaginatedResponse, assertHasData, assertStatusOneOf, delay, API_ACCOUNTS, API_BASE } from '../fixtures/api.fixture';
import axios, { AxiosInstance } from 'axios';

test.describe('API - Common Endpoints Smoke Tests', () => {
  let client: AxiosInstance;

  test.beforeAll(async () => {
    client = await getApiClient('student');
  });

  // ─── Notifications ───────────────────────────────────────────────────

  test('GET /notifications returns success', async () => {
    const res = await client.get('/notifications');
    assertSuccessResponse(res);
  });

  test('GET /notifications/unread-count returns success', async () => {
    const res = await client.get('/notifications/unread-count');
    assertSuccessResponse(res);
    expect(res.data.data).toBeDefined();
  });

  // ─── Chat ─────────────────────────────────────────────────────────────

  test('GET /chat/conversations returns success', async () => {
    const res = await client.get('/chat/conversations');
    assertSuccessResponse(res);
  });

  test('GET /chat/unread-count returns success', async () => {
    const res = await client.get('/chat/unread-count');
    assertSuccessResponse(res);
    expect(res.data.data).toBeDefined();
  });

  // ─── Profile Options ─────────────────────────────────────────────────

  test('GET /profile-options returns success', async () => {
    const res = await client.get('/profile-options');
    assertSuccessResponse(res);
    expect(res.data.data).toBeDefined();
  });

  // ─── Response structure checks ────────────────────────────────────────

  test('GET /notifications returns data array', async () => {
    const res = await client.get('/notifications');
    assertPaginatedResponse(res);
  });

  test('GET /notifications/unread-count returns numeric count', async () => {
    const res = await client.get('/notifications/unread-count');
    assertHasData(res);
    // Unread count should be a number or an object containing count
    const data = res.data.data;
    if (typeof data === 'number') {
      expect(data).toBeGreaterThanOrEqual(0);
    } else if (typeof data === 'object' && data !== null) {
      // May return { count: N } or { unread_count: N }
      const count = data.count ?? data.unread_count;
      if (count !== undefined) {
        expect(count).toBeGreaterThanOrEqual(0);
      }
    }
  });

  test('GET /chat/conversations returns data array', async () => {
    const res = await client.get('/chat/conversations');
    assertPaginatedResponse(res);
  });

  test('GET /profile-options returns structured options data', async () => {
    const res = await client.get('/profile-options');
    assertHasData(res);
    expect(res.data.data).toBeDefined();
    expect(typeof res.data.data).toBe('object');
  });

  test('GET /notifications returns meta with api_version', async () => {
    const res = await client.get('/notifications');
    expect(res.status).toBe(200);
    expect(res.data.meta).toBeDefined();
    expect(res.data.meta.api_version).toBe('v1');
  });
});
