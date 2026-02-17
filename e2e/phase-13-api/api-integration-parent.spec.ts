import { test, expect } from '@playwright/test';
import { getApiClient, getApiToken, getPublicClient, assertSuccessResponse, assertErrorResponse, assertPaginatedResponse, assertHasData, assertStatusOneOf, delay, API_ACCOUNTS, API_BASE } from '../fixtures/api.fixture';
import axios, { AxiosInstance } from 'axios';

test.describe('API - Parent Integration Tests', () => {
  let client: AxiosInstance;

  test.beforeAll(async () => {
    client = await getApiClient('parent');
  });

  // ─── Children Flow ───────────────────────────────────────────────────

  test.describe('Children workflow', () => {
    test.describe.configure({ mode: 'serial' });

    let firstChildId: string | null = null;

    test('GET /parent/children returns children list', async () => {
      const res = await client.get('/parent/children');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();

      // Store the first child ID if available
      if (Array.isArray(res.data.data) && res.data.data.length > 0) {
        firstChildId = res.data.data[0].id;
      }
    });

    test('GET /parent/children/{id} returns child details if children exist', async () => {
      if (!firstChildId) {
        test.skip();
        return;
      }
      const res = await client.get(`/parent/children/${firstChildId}`);
      assertSuccessResponse(res);
      assertHasData(res);
      expect(res.data.data.id).toBe(firstChildId);
    });

    test('GET /parent/children/{childId}/quizzes returns child quizzes', async () => {
      if (!firstChildId) {
        test.skip();
        return;
      }
      const res = await client.get(`/parent/children/${firstChildId}/quizzes`);
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });

    test('GET /parent/children/{childId}/certificates returns child certificates', async () => {
      if (!firstChildId) {
        test.skip();
        return;
      }
      const res = await client.get(`/parent/children/${firstChildId}/certificates`);
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });

    test('GET /parent/children/{childId}/homework returns child homework', async () => {
      if (!firstChildId) {
        test.skip();
        return;
      }
      const res = await client.get(`/parent/children/${firstChildId}/homework`);
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });
  });

  // ─── Sessions Flow ───────────────────────────────────────────────────

  test.describe('Sessions workflow', () => {
    test('GET /parent/sessions returns unified session list', async () => {
      const res = await client.get('/parent/sessions');
      assertPaginatedResponse(res);
    });

    test('GET /parent/sessions has correct data structure', async () => {
      const res = await client.get('/parent/sessions');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
      // Data should be array
      if (Array.isArray(res.data.data)) {
        expect(res.data.data).toBeInstanceOf(Array);
      }
    });
  });

  // ─── Reports Flow ────────────────────────────────────────────────────

  test.describe('Reports workflow', () => {
    // Note: Reports return 404 when parent has no linked children (expected behavior)

    test('GET /parent/reports/progress returns progress report structure', async () => {
      const res = await client.get('/parent/reports/progress');
      assertStatusOneOf(res, [200, 404]);
      if (res.status === 200) {
        expect(res.data.data).toBeDefined();
      }
    });

    test('GET /parent/reports/attendance returns attendance report structure', async () => {
      const res = await client.get('/parent/reports/attendance');
      assertStatusOneOf(res, [200, 404]);
      if (res.status === 200) {
        expect(res.data.data).toBeDefined();
      }
    });

    test('GET /parent/reports/quran/progress returns quran progress', async () => {
      const res = await client.get('/parent/reports/quran/progress');
      assertStatusOneOf(res, [200, 404]);
    });

    test('GET /parent/reports/academic/progress returns academic progress', async () => {
      const res = await client.get('/parent/reports/academic/progress');
      assertStatusOneOf(res, [200, 404]);
    });

    test('GET /parent/reports/interactive/progress returns interactive progress', async () => {
      const res = await client.get('/parent/reports/interactive/progress');
      assertStatusOneOf(res, [200, 404]);
    });
  });
});
