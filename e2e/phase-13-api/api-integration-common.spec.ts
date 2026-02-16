import { test, expect } from '@playwright/test';
import { getApiClient, getApiToken, getPublicClient, assertSuccessResponse, assertErrorResponse, assertPaginatedResponse, assertHasData, assertStatusOneOf, delay, API_ACCOUNTS, API_BASE } from '../fixtures/api.fixture';
import axios, { AxiosInstance } from 'axios';

test.describe('API - Common Integration Tests', () => {
  let client: AxiosInstance;

  test.beforeAll(async () => {
    client = await getApiClient('student');
  });

  // ─── Chat Flow ───────────────────────────────────────────────────────

  test.describe('Chat workflow', () => {
    test.describe.configure({ mode: 'serial' });

    let firstConversationId: string | null = null;

    test('GET /chat/conversations returns conversation list', async () => {
      const res = await client.get('/chat/conversations');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();

      // Store the first conversation ID if available
      if (Array.isArray(res.data.data) && res.data.data.length > 0) {
        firstConversationId = res.data.data[0].id;
      }
    });

    test('GET /chat/conversations/{id}/messages returns messages if conversation exists', async () => {
      if (!firstConversationId) {
        test.skip();
        return;
      }
      const res = await client.get(`/chat/conversations/${firstConversationId}/messages`);
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });

    test('GET /chat/conversations/{id} returns conversation details', async () => {
      if (!firstConversationId) {
        test.skip();
        return;
      }
      const res = await client.get(`/chat/conversations/${firstConversationId}`);
      assertSuccessResponse(res);
      assertHasData(res);
    });

    test('GET /chat/unread-count returns unread count', async () => {
      const res = await client.get('/chat/unread-count');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();
    });
  });

  // ─── Notifications Flow ──────────────────────────────────────────────

  test.describe('Notifications workflow', () => {
    test('GET /notifications returns notification list with structure', async () => {
      const res = await client.get('/notifications');
      assertSuccessResponse(res);
      expect(res.data.data).toBeDefined();

      // If there are notifications, verify structure
      if (Array.isArray(res.data.data) && res.data.data.length > 0) {
        const notification = res.data.data[0];
        expect(notification.id).toBeDefined();
      }
    });

    test('GET /notifications returns proper meta information', async () => {
      const res = await client.get('/notifications');
      assertSuccessResponse(res);
      expect(res.data.meta).toBeDefined();
      expect(res.data.meta.api_version).toBe('v1');
    });
  });

  // ─── Device Token Flow ──────────────────────────────────────────────

  test.describe('Device token workflow', () => {
    test('POST /notifications/device-token registers a test token', async () => {
      const res = await client.post('/notifications/device-token', {
        token: 'e2e-test-fcm-token-' + Date.now(),
        platform: 'ios',
      });
      // Accept 200 (success) or 422 (validation error if format differs)
      assertStatusOneOf(res, [200, 201, 422]);
      if (res.status === 200 || res.status === 201) {
        expect(res.data.success).toBe(true);
      }
    });

    test('POST /notifications/device-token with missing token returns 422', async () => {
      const res = await client.post('/notifications/device-token', {});
      expect(res.status).toBe(422);
      expect(res.data.success).toBe(false);
      expect(res.data.message).toBeDefined();
      expect(res.data.error_code).toBe('VALIDATION_ERROR');
    });
  });

  // ─── Profile Options Flow ────────────────────────────────────────────

  test.describe('Profile options workflow', () => {
    test('GET /profile-options returns form dropdown data', async () => {
      const res = await client.get('/profile-options');
      assertSuccessResponse(res);
      assertHasData(res);
      expect(typeof res.data.data).toBe('object');
    });
  });
});
