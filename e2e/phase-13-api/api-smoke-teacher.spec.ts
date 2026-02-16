import { test, expect } from '@playwright/test';
import { getApiClient, getApiToken, getPublicClient, assertSuccessResponse, assertErrorResponse, assertPaginatedResponse, assertHasData, assertStatusOneOf, delay, API_ACCOUNTS, API_BASE } from '../fixtures/api.fixture';
import axios, { AxiosInstance } from 'axios';

test.describe('API - Quran Teacher Smoke Tests', () => {
  let client: AxiosInstance;

  test.beforeAll(async () => {
    client = await getApiClient('teacher');
  });

  // ─── Dashboard ────────────────────────────────────────────────────────

  test('GET /teacher/dashboard returns success', async () => {
    const res = await client.get('/teacher/dashboard');
    assertSuccessResponse(res);
  });

  // ─── Schedule ─────────────────────────────────────────────────────────

  test('GET /teacher/schedule returns success', async () => {
    const res = await client.get('/teacher/schedule');
    assertSuccessResponse(res);
  });

  test('GET /teacher/schedule/2026-02-16 returns success', async () => {
    const res = await client.get('/teacher/schedule/2026-02-16');
    assertSuccessResponse(res);
  });

  // ─── Quran Circles ───────────────────────────────────────────────────

  test('GET /teacher/quran/circles/individual returns success', async () => {
    const res = await client.get('/teacher/quran/circles/individual');
    assertSuccessResponse(res);
  });

  test('GET /teacher/quran/circles/group returns success', async () => {
    const res = await client.get('/teacher/quran/circles/group');
    assertSuccessResponse(res);
  });

  // ─── Quran Sessions ──────────────────────────────────────────────────

  test('GET /teacher/quran/sessions returns success', async () => {
    const res = await client.get('/teacher/quran/sessions');
    assertSuccessResponse(res);
  });

  // ─── Students ─────────────────────────────────────────────────────────

  test('GET /teacher/students returns success', async () => {
    const res = await client.get('/teacher/students');
    assertSuccessResponse(res);
  });

  // ─── Homework ─────────────────────────────────────────────────────────

  test('GET /teacher/homework returns success', async () => {
    const res = await client.get('/teacher/homework');
    assertSuccessResponse(res);
  });

  // ─── Earnings ─────────────────────────────────────────────────────────

  test('GET /teacher/earnings returns success', async () => {
    const res = await client.get('/teacher/earnings');
    assertSuccessResponse(res);
  });

  test('GET /teacher/earnings/history returns success', async () => {
    const res = await client.get('/teacher/earnings/history');
    assertSuccessResponse(res);
  });

  test('GET /teacher/payouts returns success', async () => {
    const res = await client.get('/teacher/payouts');
    assertSuccessResponse(res);
  });

  // ─── Profile ──────────────────────────────────────────────────────────

  test('GET /teacher/profile returns success', async () => {
    const res = await client.get('/teacher/profile');
    assertSuccessResponse(res);
  });

  // ─── Response structure checks ────────────────────────────────────────

  test('GET /teacher/quran/sessions returns data array', async () => {
    const res = await client.get('/teacher/quran/sessions');
    assertPaginatedResponse(res);
  });

  test('GET /teacher/profile returns data object', async () => {
    const res = await client.get('/teacher/profile');
    assertHasData(res);
    expect(res.data.data).toBeDefined();
  });

  test('GET /teacher/dashboard returns structured data', async () => {
    const res = await client.get('/teacher/dashboard');
    assertHasData(res);
    expect(res.data.data).toBeDefined();
  });
});

test.describe('API - Academic Teacher Smoke Tests', () => {
  let client: AxiosInstance;

  test.beforeAll(async () => {
    client = await getApiClient('academic-teacher');
  });

  // ─── Dashboard (shared teacher endpoint) ──────────────────────────────

  test('GET /teacher/dashboard returns success for academic teacher', async () => {
    const res = await client.get('/teacher/dashboard');
    assertSuccessResponse(res);
  });

  // ─── Schedule (shared teacher endpoint) ───────────────────────────────

  test('GET /teacher/schedule returns success for academic teacher', async () => {
    const res = await client.get('/teacher/schedule');
    assertSuccessResponse(res);
  });

  // ─── Academic Lessons ─────────────────────────────────────────────────

  test('GET /teacher/academic/lessons returns success', async () => {
    const res = await client.get('/teacher/academic/lessons');
    assertSuccessResponse(res);
  });

  // ─── Academic Courses ─────────────────────────────────────────────────

  test('GET /teacher/academic/courses returns success', async () => {
    const res = await client.get('/teacher/academic/courses');
    assertSuccessResponse(res);
  });

  // ─── Academic Sessions ────────────────────────────────────────────────

  test('GET /teacher/academic/sessions returns success', async () => {
    const res = await client.get('/teacher/academic/sessions');
    assertSuccessResponse(res);
  });

  // ─── Students (shared teacher endpoint) ───────────────────────────────

  test('GET /teacher/students returns success for academic teacher', async () => {
    const res = await client.get('/teacher/students');
    assertSuccessResponse(res);
  });

  // ─── Profile (shared teacher endpoint) ────────────────────────────────

  test('GET /teacher/profile returns success for academic teacher', async () => {
    const res = await client.get('/teacher/profile');
    assertSuccessResponse(res);
  });

  // ─── Response structure checks ────────────────────────────────────────

  test('GET /teacher/academic/sessions returns data array', async () => {
    const res = await client.get('/teacher/academic/sessions');
    assertPaginatedResponse(res);
  });

  test('GET /teacher/academic/lessons returns data array', async () => {
    const res = await client.get('/teacher/academic/lessons');
    assertPaginatedResponse(res);
  });

  test('GET /teacher/academic/courses returns data array', async () => {
    const res = await client.get('/teacher/academic/courses');
    assertPaginatedResponse(res);
  });
});
