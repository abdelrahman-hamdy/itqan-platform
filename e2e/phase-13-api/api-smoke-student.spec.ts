import { test, expect } from '@playwright/test';
import { getApiClient, getApiToken, getPublicClient, assertSuccessResponse, assertErrorResponse, assertPaginatedResponse, assertHasData, assertStatusOneOf, delay, API_ACCOUNTS, API_BASE } from '../fixtures/api.fixture';
import axios, { AxiosInstance } from 'axios';

test.describe('API - Student Smoke Tests', () => {
  let client: AxiosInstance;

  test.beforeAll(async () => {
    client = await getApiClient('student');
  });

  // ─── Dashboard ────────────────────────────────────────────────────────

  test('GET /student/dashboard returns success', async () => {
    const res = await client.get('/student/dashboard');
    assertSuccessResponse(res);
  });

  // ─── Sessions (Unified) ──────────────────────────────────────────────

  test('GET /student/sessions returns success', async () => {
    const res = await client.get('/student/sessions');
    assertSuccessResponse(res);
  });

  test('GET /student/sessions/upcoming returns success', async () => {
    const res = await client.get('/student/sessions/upcoming');
    assertSuccessResponse(res);
  });

  test('GET /student/sessions/today returns success', async () => {
    const res = await client.get('/student/sessions/today');
    assertSuccessResponse(res);
  });

  // ─── Sessions (Type-specific) ────────────────────────────────────────

  test('GET /student/sessions/quran returns success', async () => {
    const res = await client.get('/student/sessions/quran');
    assertSuccessResponse(res);
  });

  test('GET /student/sessions/academic returns success', async () => {
    const res = await client.get('/student/sessions/academic');
    assertSuccessResponse(res);
  });

  test('GET /student/sessions/interactive returns success', async () => {
    const res = await client.get('/student/sessions/interactive');
    assertSuccessResponse(res);
  });

  // ─── Subscriptions ───────────────────────────────────────────────────

  test('GET /student/subscriptions returns success', async () => {
    const res = await client.get('/student/subscriptions');
    assertSuccessResponse(res);
  });

  // ─── Homework ────────────────────────────────────────────────────────

  test('GET /student/homework returns success', async () => {
    const res = await client.get('/student/homework');
    assertSuccessResponse(res);
  });

  // ─── Quizzes ─────────────────────────────────────────────────────────

  test('GET /student/quizzes returns success', async () => {
    const res = await client.get('/student/quizzes');
    assertSuccessResponse(res);
  });

  test('GET /student/quizzes/history returns success', async () => {
    const res = await client.get('/student/quizzes/history');
    assertSuccessResponse(res);
  });

  // ─── Certificates ────────────────────────────────────────────────────

  test('GET /student/certificates returns success', async () => {
    const res = await client.get('/student/certificates');
    assertSuccessResponse(res);
  });

  // ─── Payments ────────────────────────────────────────────────────────

  test('GET /student/payments returns success', async () => {
    const res = await client.get('/student/payments');
    assertSuccessResponse(res);
  });

  // ─── Calendar ────────────────────────────────────────────────────────

  test('GET /student/calendar returns success', async () => {
    const res = await client.get('/student/calendar');
    assertSuccessResponse(res);
  });

  test('GET /student/calendar/month/2026/2 returns success', async () => {
    const res = await client.get('/student/calendar/month/2026/2');
    assertSuccessResponse(res);
  });

  // ─── Profile ─────────────────────────────────────────────────────────

  test('GET /student/profile returns success', async () => {
    const res = await client.get('/student/profile');
    assertSuccessResponse(res);
  });

  // ─── Teachers ────────────────────────────────────────────────────────

  test('GET /student/teachers/quran returns success', async () => {
    const res = await client.get('/student/teachers/quran');
    assertSuccessResponse(res);
  });

  test('GET /student/teachers/academic returns success', async () => {
    const res = await client.get('/student/teachers/academic');
    assertSuccessResponse(res);
  });

  // ─── Circles ─────────────────────────────────────────────────────────

  test('GET /student/circles/quran returns success', async () => {
    const res = await client.get('/student/circles/quran');
    assertSuccessResponse(res);
  });

  // ─── Trial Requests ──────────────────────────────────────────────────

  test('GET /student/trial-requests returns success', async () => {
    const res = await client.get('/student/trial-requests');
    assertSuccessResponse(res);
  });

  // ─── Courses ─────────────────────────────────────────────────────────

  test('GET /student/courses/interactive returns success', async () => {
    const res = await client.get('/student/courses/interactive');
    assertSuccessResponse(res);
  });

  test('GET /student/courses/recorded returns success', async () => {
    const res = await client.get('/student/courses/recorded');
    assertSuccessResponse(res);
  });

  // ─── Search ──────────────────────────────────────────────────────────

  test('GET /student/search?q=test returns success', async () => {
    const res = await client.get('/student/search?q=test');
    assertSuccessResponse(res);
  });

  test('GET /student/search/suggestions returns success', async () => {
    const res = await client.get('/student/search/suggestions');
    assertSuccessResponse(res);
  });

  // ─── Response structure checks ───────────────────────────────────────

  test('GET /student/sessions returns data array', async () => {
    const res = await client.get('/student/sessions');
    assertPaginatedResponse(res);
  });

  test('GET /student/profile returns data object with user info', async () => {
    const res = await client.get('/student/profile');
    assertHasData(res);
    expect(res.data.data).toBeDefined();
  });

  test('GET /student/dashboard returns structured dashboard data', async () => {
    const res = await client.get('/student/dashboard');
    assertHasData(res);
    expect(res.data.data).toBeDefined();
  });

  test('GET /student/subscriptions returns data array', async () => {
    const res = await client.get('/student/subscriptions');
    assertPaginatedResponse(res);
  });

  test('GET /student/homework returns data array', async () => {
    const res = await client.get('/student/homework');
    assertPaginatedResponse(res);
  });

  test('GET /student/dashboard returns meta with api_version', async () => {
    const res = await client.get('/student/dashboard');
    expect(res.status).toBe(200);
    expect(res.data.meta).toBeDefined();
    expect(res.data.meta.api_version).toBe('v1');
  });
});
