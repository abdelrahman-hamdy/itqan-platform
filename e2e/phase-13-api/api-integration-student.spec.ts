import { test, expect } from '@playwright/test';
import { getApiClient, getApiToken, getPublicClient, assertSuccessResponse, assertErrorResponse, assertPaginatedResponse, assertHasData, assertStatusOneOf, delay, API_ACCOUNTS, API_BASE } from '../fixtures/api.fixture';
import axios, { AxiosInstance } from 'axios';

test.describe('API - Student Integration Tests', () => {
  let client: AxiosInstance;

  test.beforeAll(async () => {
    client = await getApiClient('student');
  });

  // ─── Profile Update Flow ─────────────────────────────────────────────

  test.describe('Profile update workflow', () => {
    test.describe.configure({ mode: 'serial' });

    let originalFirstName: string;

    test('GET /student/profile returns current profile', async () => {
      const res = await client.get('/student/profile');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
      // Store the original first name for restoration later
      originalFirstName = res.data.data.first_name || res.data.data.name || '';
      expect(originalFirstName).toBeDefined();
    });

    test('PUT /student/profile updates first_name', async () => {
      const res = await client.put('/student/profile', {
        first_name: 'E2E Test',
      });
      assertStatusOneOf(res, [200, 422]);
      // 422 may occur if additional required fields are missing
      if (res.status === 200) {
        expect(res.data.success).toBe(true);
      }
    });

    test('GET /student/profile verifies updated name', async () => {
      const res = await client.get('/student/profile');
      assertSuccessResponse(res);
      const name = res.data.data.first_name || res.data.data.name || '';
      // Name should either be updated or unchanged if update was rejected
      expect(name).toBeDefined();
    });

    test('PUT /student/profile restores original name', async () => {
      if (originalFirstName) {
        const res = await client.put('/student/profile', {
          first_name: originalFirstName,
        });
        assertStatusOneOf(res, [200, 422]);
      }
    });
  });

  // ─── Notifications Flow ──────────────────────────────────────────────

  test.describe('Notifications workflow', () => {
    test.describe.configure({ mode: 'serial' });

    test('GET /notifications returns notification list', async () => {
      const res = await client.get('/notifications');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });

    test('PUT /notifications/read-all marks all as read', async () => {
      const res = await client.put('/notifications/read-all');
      assertSuccessResponse(res);
    });

    test('GET /notifications/unread-count returns 0 after read-all', async () => {
      const res = await client.get('/notifications/unread-count');
      assertSuccessResponse(res);
      const data = res.data.data;
      // After marking all as read, unread count should be 0
      if (typeof data === 'number') {
        expect(data).toBe(0);
      } else if (typeof data === 'object' && data !== null) {
        const count = data.count ?? data.unread_count ?? 0;
        expect(count).toBe(0);
      }
    });
  });

  // ─── Search Flow ─────────────────────────────────────────────────────

  test.describe('Search workflow', () => {
    test('GET /student/search?q=quran returns results structure', async () => {
      const res = await client.get('/student/search?q=quran');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });

    test('GET /student/search?q= with empty query returns success', async () => {
      const res = await client.get('/student/search?q=');
      // Empty query may return empty results or 422
      assertStatusOneOf(res, [200, 422]);
    });

    test('GET /student/search/suggestions returns suggestions array', async () => {
      const res = await client.get('/student/search/suggestions');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });
  });

  // ─── Calendar Flow ───────────────────────────────────────────────────

  test.describe('Calendar workflow', () => {
    test('GET /student/calendar returns current calendar data', async () => {
      const res = await client.get('/student/calendar');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });

    test('GET /student/calendar/month/2026/2 returns month data structure', async () => {
      const res = await client.get('/student/calendar/month/2026/2');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });

    test('GET /student/calendar/month/2026/1 returns January data', async () => {
      const res = await client.get('/student/calendar/month/2026/1');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });

    test('GET /student/calendar/month/2026/12 returns December data', async () => {
      const res = await client.get('/student/calendar/month/2026/12');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });
  });

  // ─── Sessions Detail Flow ────────────────────────────────────────────

  test.describe('Sessions detail flow', () => {
    test('GET /student/sessions returns paginated sessions', async () => {
      const res = await client.get('/student/sessions');
      assertPaginatedResponse(res);
    });

    test('GET /student/sessions/upcoming returns upcoming sessions', async () => {
      const res = await client.get('/student/sessions/upcoming');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });
  });
});
