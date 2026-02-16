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

test.describe('Cross-Role Report Consistency', () => {
  test('admin can view session reports', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'superadmin');
    await page.goto(`${ADMIN_URL}/admin/student-session-reports`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
    await close();
  });

  test('teacher can view session reports', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'quran-teacher');
    await page.goto(`${ACADEMY_URL}/teacher-panel/student-session-reports`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
    await close();
  });

  test('supervisor can view session reports', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'supervisor');
    await page.goto(`${ACADEMY_URL}/supervisor-panel/monitored-session-reports`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
    await close();
  });

  test('admin can view payments', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'superadmin');
    await page.goto(`${ADMIN_URL}/admin/payments`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
    await close();
  });
});
