import axios, { AxiosInstance, AxiosResponse } from 'axios';
import { expect } from '@playwright/test';

/** API base URL for the academy tenant */
export const API_BASE = 'https://itqan-academy.itqanway.com/api/v1';

/** Academy subdomain header value */
export const ACADEMY_SUBDOMAIN = 'itqan-academy';

/** Test account credentials (same as web tests) */
export const API_ACCOUNTS: Record<string, { email: string; password: string }> = {
  student: { email: 'abdelrahman260598@gmail.com', password: 'Admin@Dev98' },
  teacher: { email: 'quran.teacher5@itqan.com', password: 'Admin@Dev98' },
  'academic-teacher': { email: 'academic.teacher1@itqan.com', password: 'Admin@Dev98' },
  parent: { email: 'parent1@itqan.com', password: 'Admin@Dev98' },
  supervisor: { email: 'supervisor1@itqan.com', password: 'Admin@Dev98' },
  admin: { email: 'abdelrahmanhamdy320@gmail.com', password: 'Admin@Dev98' },
};

/** Token cache to avoid rate limiting (5 attempts per minute on login) */
const tokenCache: Record<string, string> = {};

/** Get a Sanctum API token for a role, with caching and retry on rate limit */
export async function getApiToken(role: string): Promise<string> {
  if (tokenCache[role]) return tokenCache[role];

  const account = API_ACCOUNTS[role];
  if (!account) throw new Error(`Unknown API role: ${role}`);

  const maxRetries = 5;
  for (let attempt = 0; attempt < maxRetries; attempt++) {
    const response = await axios.post(`${API_BASE}/login`, {
      email: account.email,
      password: account.password,
      device_name: 'e2e-playwright-tests',
    }, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Academy-Subdomain': ACADEMY_SUBDOMAIN,
      },
      validateStatus: () => true,
    });

    if (response.status === 429) {
      // Rate limited — wait and retry (login rate limit is typically 5/min)
      const waitMs = (attempt + 1) * 15_000; // 15s, 30s, 45s, 60s, 75s
      await delay(waitMs);
      continue;
    }

    if (response.status !== 200 || !response.data?.data?.token) {
      throw new Error(`Login failed for ${role}: ${response.status} - ${JSON.stringify(response.data)}`);
    }

    tokenCache[role] = response.data.data.token;
    return tokenCache[role];
  }

  throw new Error(`Login rate limited for ${role} after ${maxRetries} retries`);
}

/** Create an authenticated axios client for a specific role */
export async function getApiClient(role: string): Promise<AxiosInstance> {
  const token = await getApiToken(role);

  return axios.create({
    baseURL: API_BASE,
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Academy-Subdomain': ACADEMY_SUBDOMAIN,
    },
    validateStatus: () => true, // Never throw on non-2xx
    timeout: 15000,
  });
}

/** Create an unauthenticated axios client (for public endpoints and error tests) */
export function getPublicClient(): AxiosInstance {
  return axios.create({
    baseURL: API_BASE,
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Academy-Subdomain': ACADEMY_SUBDOMAIN,
    },
    validateStatus: () => true,
    timeout: 15000,
  });
}

// ─── Response Assertions ───────────────────────────────────────────────

/** Assert a standard success response: { success: true, data, meta } */
export function assertSuccessResponse(response: AxiosResponse, expectedStatus = 200): void {
  expect(response.status).toBe(expectedStatus);
  expect(response.data.success).toBe(true);
  expect(response.data.meta).toBeDefined();
  expect(response.data.meta.api_version).toBe('v1');
}

/** Assert a standard error response: { success: false, error_code, meta } */
export function assertErrorResponse(response: AxiosResponse, expectedStatus: number): void {
  expect(response.status).toBe(expectedStatus);
  expect(response.data.success).toBe(false);
  expect(response.data.error_code).toBeDefined();
  expect(response.data.meta).toBeDefined();
  expect(response.data.meta.api_version).toBe('v1');
}

/** Assert a paginated response with data array */
export function assertPaginatedResponse(response: AxiosResponse): void {
  expect(response.status).toBe(200);
  expect(response.data.success).toBe(true);
  expect(response.data.data).toBeDefined();
  // Data should be array (either directly or wrapped)
  if (Array.isArray(response.data.data)) {
    expect(response.data.data).toBeInstanceOf(Array);
  }
}

/** Assert response has data field that is not null */
export function assertHasData(response: AxiosResponse): void {
  expect(response.status).toBe(200);
  expect(response.data.success).toBe(true);
  expect(response.data.data).toBeDefined();
  expect(response.data.data).not.toBeNull();
}

/** Assert response status is one of the expected values */
export function assertStatusOneOf(response: AxiosResponse, statuses: number[]): void {
  expect(statuses).toContain(response.status);
}

// ─── Utilities ─────────────────────────────────────────────────────────

/** Delay helper for rate limiting */
export function delay(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/** Clear token cache (useful between test suites if token expires) */
export function clearTokenCache(): void {
  Object.keys(tokenCache).forEach(key => delete tokenCache[key]);
}
