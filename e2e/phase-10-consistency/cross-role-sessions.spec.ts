import { test, expect, Browser, Page } from '@playwright/test';
import { assertNoServerError, waitForLivewire, assertNotLoginPage } from '../fixtures/filament.fixture';
import * as fs from 'fs';
import * as path from 'path';

const ADMIN_URL = 'https://itqanway.com';
const ACADEMY_URL = 'https://itqan-academy.itqanway.com';

async function createAuthPage(browser: Browser, role: string): Promise<{ page: Page; close: () => Promise<void> }> {
  const statePath = path.join(process.cwd(), `e2e/auth/${role}.json`);
  const ctx = await browser.newContext(fs.existsSync(statePath) ? { storageState: statePath } : {});
  const page = await ctx.newPage();
  return { page, close: () => ctx.close() };
}

test.describe('Cross-Role Session Consistency', () => {
  test('admin can see quran sessions', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'superadmin');
    await page.goto(`${ADMIN_URL}/admin/quran-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
    await close();
  });

  test('teacher can see quran sessions', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'quran-teacher');
    await page.goto(`${ACADEMY_URL}/teacher-panel/quran-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
    await close();
  });

  test('student can see subscriptions page', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'student');
    await page.goto(`${ACADEMY_URL}/subscriptions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await close();
  });

  test('supervisor can see monitored sessions', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'supervisor');
    await page.goto(`${ACADEMY_URL}/supervisor-panel/monitored-all-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
    await close();
  });

  test('admin can see academic sessions', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'superadmin');
    await page.goto(`${ADMIN_URL}/admin/academic-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
    await close();
  });
});
