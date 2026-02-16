import { test, expect } from '@playwright/test';
import { getApiClient, getApiToken, getPublicClient, assertSuccessResponse, assertErrorResponse, assertPaginatedResponse, assertHasData, assertStatusOneOf, delay, API_ACCOUNTS, API_BASE } from '../fixtures/api.fixture';
import axios, { AxiosInstance } from 'axios';

test.describe('API - Admin Smoke Tests', () => {
  let adminClient: AxiosInstance;

  test.beforeAll(async () => {
    adminClient = await getApiClient('admin');
  });

  // ─── Session Monitoring ──────────────────────────────────────────────

  test('GET /admin/sessions returns success for admin', async () => {
    const res = await adminClient.get('/admin/sessions');
    assertSuccessResponse(res);
  });

  test('GET /admin/sessions returns data array', async () => {
    const res = await adminClient.get('/admin/sessions');
    assertPaginatedResponse(res);
  });

  test('GET /admin/sessions returns meta with api_version', async () => {
    const res = await adminClient.get('/admin/sessions');
    expect(res.status).toBe(200);
    expect(res.data.success).toBe(true);
    expect(res.data.meta).toBeDefined();
    expect(res.data.meta.api_version).toBe('v1');
  });
});

test.describe('API - Supervisor Smoke Tests', () => {
  let supervisorClient: AxiosInstance;

  test.beforeAll(async () => {
    supervisorClient = await getApiClient('supervisor');
  });

  // ─── Supervised Chat Groups ──────────────────────────────────────────

  test('GET /supervisor/chat/supervised-groups returns success', async () => {
    const res = await supervisorClient.get('/supervisor/chat/supervised-groups');
    assertSuccessResponse(res);
  });

  test('GET /supervisor/chat/supervised-groups returns data', async () => {
    const res = await supervisorClient.get('/supervisor/chat/supervised-groups');
    assertHasData(res);
    expect(res.data.data).toBeDefined();
  });
});
