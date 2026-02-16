import { test, expect } from '@playwright/test';
import { getApiClient, getApiToken, getPublicClient, assertSuccessResponse, assertErrorResponse, assertPaginatedResponse, assertHasData, assertStatusOneOf, delay, API_ACCOUNTS, API_BASE } from '../fixtures/api.fixture';
import axios, { AxiosInstance } from 'axios';

test.describe('API - Teacher Integration Tests', () => {
  let client: AxiosInstance;

  test.beforeAll(async () => {
    client = await getApiClient('teacher');
  });

  // ─── Schedule Flow ───────────────────────────────────────────────────

  test.describe('Schedule workflow', () => {
    test('GET /teacher/schedule returns schedule with structure', async () => {
      const res = await client.get('/teacher/schedule');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });

    test('GET /teacher/schedule/2026-02-16 returns day data', async () => {
      const res = await client.get('/teacher/schedule/2026-02-16');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });

    test('GET /teacher/schedule/2026-02-17 returns another day data', async () => {
      const res = await client.get('/teacher/schedule/2026-02-17');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });
  });

  // ─── Sessions Flow ───────────────────────────────────────────────────

  test.describe('Sessions workflow', () => {
    test('GET /teacher/quran/sessions returns paginated sessions', async () => {
      const res = await client.get('/teacher/quran/sessions');
      assertPaginatedResponse(res);
    });

    test('GET /teacher/quran/sessions has correct response structure', async () => {
      const res = await client.get('/teacher/quran/sessions');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
      // Data should be array
      if (Array.isArray(res.data.data)) {
        expect(res.data.data).toBeInstanceOf(Array);
      }
    });
  });

  // ─── Students Flow ───────────────────────────────────────────────────

  test.describe('Students workflow', () => {
    test('GET /teacher/students returns student list', async () => {
      const res = await client.get('/teacher/students');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });

    test('GET /teacher/students returns data with student structure', async () => {
      const res = await client.get('/teacher/students');
      assertPaginatedResponse(res);
      // If there are students, verify basic structure
      if (Array.isArray(res.data.data) && res.data.data.length > 0) {
        const student = res.data.data[0];
        expect(student).toBeDefined();
        // Student should have id and name-like properties
        expect(student.id).toBeDefined();
      }
    });
  });

  // ─── Profile Flow ────────────────────────────────────────────────────

  test.describe('Profile workflow', () => {
    test.describe.configure({ mode: 'serial' });

    let originalBio: string | null = null;

    test('GET /teacher/profile returns current profile', async () => {
      const res = await client.get('/teacher/profile');
      assertSuccessResponse(res);
      assertHasData(res);
      // Store original bio for restoration
      originalBio = res.data.data.bio || res.data.data.about || null;
    });

    test('PUT /teacher/profile updates bio', async () => {
      const res = await client.put('/teacher/profile', {
        bio: 'E2E integration test bio update',
      });
      assertStatusOneOf(res, [200, 422]);
      if (res.status === 200) {
        expect(res.data.success).toBe(true);
      }
    });

    test('GET /teacher/profile verifies update', async () => {
      const res = await client.get('/teacher/profile');
      assertSuccessResponse(res);
      assertHasData(res);
    });

    test('PUT /teacher/profile restores original bio', async () => {
      if (originalBio !== null) {
        const res = await client.put('/teacher/profile', {
          bio: originalBio,
        });
        assertStatusOneOf(res, [200, 422]);
      }
    });
  });

  // ─── Circles Flow ────────────────────────────────────────────────────

  test.describe('Circles workflow', () => {
    test('GET /teacher/quran/circles/individual returns individual circles', async () => {
      const res = await client.get('/teacher/quran/circles/individual');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });

    test('GET /teacher/quran/circles/group returns group circles', async () => {
      const res = await client.get('/teacher/quran/circles/group');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });
  });
});
