import { test as base, Page, BrowserContext, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const ACADEMY_URL = 'https://e2e-test.itqanway.com';
const ADMIN_URL = 'https://itqanway.com';

export const TEST_ACCOUNTS = {
  superadmin: { email: 'abdelrahmanhamdy320@gmail.com', password: 'Admin@Dev98' },
  supervisor: { email: 'e2e-supervisor@itqan.com', password: 'Admin@Dev98' },
  student: { email: 'e2e-student@itqan.com', password: 'Admin@Dev98' },
  'quran-teacher': { email: 'e2e-teacher@itqan.com', password: 'Admin@Dev98' },
  'academic-teacher': { email: 'e2e-academic@itqan.com', password: 'Admin@Dev98' },
  parent: { email: 'e2e-parent@itqan.com', password: 'Admin@Dev98' },
  admin: { email: 'e2e-admin@itqan.com', password: 'Admin@Dev98' },
};

/** Login via the academy web login form (standard HTML form) */
export async function loginViaUI(page: Page, email: string, password: string, baseUrl = ACADEMY_URL) {
  await page.goto(`${baseUrl}/login`);
  await page.waitForLoadState('networkidle');
  // Academy login uses standard HTML inputs with name="email" / name="password"
  await page.fill('input[name="email"], input#email', email);
  await page.fill('input[name="password"], input#password', password);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  await page.waitForURL(url => !url.toString().includes('/login'), { timeout: 30000 });
}

/** Login via Filament admin panel at itqanway.com/admin */
export async function loginAsFilamentAdmin(page: Page, email: string, password: string) {
  await page.goto(`${ADMIN_URL}/admin/login`);
  await page.waitForLoadState('networkidle');
  // If already authenticated (redirected to dashboard), skip login
  if (!page.url().includes('/login')) return;
  // Filament uses Livewire forms with data.* prefixed IDs
  const emailInput = page.locator('input[id="data.email"], input[name="data.email"], input[name="email"]').first();
  const passwordInput = page.locator('input[id="data.password"], input[name="data.password"], input[name="password"]').first();
  await emailInput.waitFor({ state: 'visible', timeout: 15000 });
  await emailInput.fill(email);
  await passwordInput.fill(password);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  await page.waitForURL(url => !url.toString().includes('/login'), { timeout: 30000 });
}

/** Login via Filament tenant panel (teacher-panel, supervisor-panel, etc.) */
export async function loginAsFilamentPanel(page: Page, panelPath: string, email: string, password: string) {
  await page.goto(`${ACADEMY_URL}/${panelPath}/login`);
  await page.waitForLoadState('networkidle');
  // If already authenticated (redirected to panel dashboard), skip login
  if (!page.url().includes('/login')) return;
  // Filament uses Livewire forms with data.* prefixed IDs
  const emailInput = page.locator('input[id="data.email"], input[name="data.email"], input[name="email"]').first();
  const passwordInput = page.locator('input[id="data.password"], input[name="data.password"], input[name="password"]').first();
  await emailInput.waitFor({ state: 'visible', timeout: 15000 });
  await emailInput.fill(email);
  await passwordInput.fill(password);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  await page.waitForURL(url => !url.toString().includes('/login'), { timeout: 30000 });
}

// ─────────────────────────────────────────────────────────────────────
// Worker-scoped authenticated page fixtures
// These create ONE context per worker, load stored auth, verify it works,
// and re-login only if session expired. The same context is shared across
// all tests in the worker, keeping the session fresh.
// ─────────────────────────────────────────────────────────────────────

function loadStorageState(role: string): string | undefined {
  const statePath = path.join(process.cwd(), `e2e/auth/${role}.json`);
  if (fs.existsSync(statePath)) return statePath;
  return undefined;
}

/** Admin panel test - provides a shared authenticated adminPage */
export const adminTest = base.extend<{}, { adminPage: Page }>({
  adminPage: [async ({ browser }, use) => {
    const storageState = loadStorageState('superadmin');
    const context = await browser.newContext(storageState ? { storageState } : {});
    const page = await context.newPage();
    // Navigate to dashboard to verify/establish auth
    await page.goto(`${ADMIN_URL}/admin`);
    await page.waitForLoadState('networkidle');
    // Re-login only if session expired
    if (page.url().includes('/login')) {
      await loginAsFilamentAdmin(page, TEST_ACCOUNTS.superadmin.email, TEST_ACCOUNTS.superadmin.password);
    }
    await use(page);
    await context.close();
  }, { scope: 'worker' }],
});

/** Student portal test - provides a shared authenticated studentPage */
export const studentTest = base.extend<{}, { studentPage: Page }>({
  studentPage: [async ({ browser }, use) => {
    const storageState = loadStorageState('student');
    const context = await browser.newContext(storageState ? { storageState } : {});
    const page = await context.newPage();
    await page.goto(`${ACADEMY_URL}/dashboard`);
    await page.waitForLoadState('networkidle');
    if (page.url().includes('/login')) {
      await loginViaUI(page, TEST_ACCOUNTS.student.email, TEST_ACCOUNTS.student.password);
    }
    await use(page);
    await context.close();
  }, { scope: 'worker' }],
});

/** Quran teacher panel test - provides a shared authenticated teacherPage */
export const teacherTest = base.extend<{}, { teacherPage: Page }>({
  teacherPage: [async ({ browser }, use) => {
    const storageState = loadStorageState('quran-teacher');
    const context = await browser.newContext(storageState ? { storageState } : {});
    const page = await context.newPage();
    await page.goto(`${ACADEMY_URL}/teacher-panel`);
    await page.waitForLoadState('networkidle');
    if (page.url().includes('/login')) {
      await loginAsFilamentPanel(page, 'teacher-panel', TEST_ACCOUNTS['quran-teacher'].email, TEST_ACCOUNTS['quran-teacher'].password);
    }
    await use(page);
    await context.close();
  }, { scope: 'worker' }],
});

/** Supervisor panel test - provides a shared authenticated supervisorPage */
export const supervisorTest = base.extend<{}, { supervisorPage: Page }>({
  supervisorPage: [async ({ browser }, use) => {
    const storageState = loadStorageState('supervisor');
    const context = await browser.newContext(storageState ? { storageState } : {});
    const page = await context.newPage();
    await page.goto(`${ACADEMY_URL}/supervisor-panel`);
    await page.waitForLoadState('networkidle');
    if (page.url().includes('/login')) {
      await loginAsFilamentPanel(page, 'supervisor-panel', TEST_ACCOUNTS.supervisor.email, TEST_ACCOUNTS.supervisor.password);
    }
    await use(page);
    await context.close();
  }, { scope: 'worker' }],
});

/** Academic teacher panel test - provides a shared authenticated academicTeacherPage */
export const academicTeacherTest = base.extend<{}, { academicTeacherPage: Page }>({
  academicTeacherPage: [async ({ browser }, use) => {
    const storageState = loadStorageState('academic-teacher');
    const context = await browser.newContext(storageState ? { storageState } : {});
    const page = await context.newPage();
    await page.goto(`${ACADEMY_URL}/academic-teacher-panel`);
    await page.waitForLoadState('networkidle');
    if (page.url().includes('/login')) {
      await loginAsFilamentPanel(page, 'academic-teacher-panel', TEST_ACCOUNTS['academic-teacher'].email, TEST_ACCOUNTS['academic-teacher'].password);
    }
    await use(page);
    await context.close();
  }, { scope: 'worker' }],
});

/** Parent portal test - provides a shared authenticated parentPage */
export const parentTest = base.extend<{}, { parentPage: Page }>({
  parentPage: [async ({ browser }, use) => {
    const storageState = loadStorageState('parent');
    const context = await browser.newContext(storageState ? { storageState } : {});
    const page = await context.newPage();
    await page.goto(`${ACADEMY_URL}/parent/dashboard`);
    await page.waitForLoadState('networkidle');
    if (page.url().includes('/login')) {
      await loginViaUI(page, TEST_ACCOUNTS.parent.email, TEST_ACCOUNTS.parent.password);
    }
    await use(page);
    await context.close();
  }, { scope: 'worker' }],
});
