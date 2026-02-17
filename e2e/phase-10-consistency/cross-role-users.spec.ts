import { test, expect, Browser, Page } from '@playwright/test';
import { assertNoServerError, waitForLivewire, assertNotLoginPage } from '../fixtures/filament.fixture';
import * as fs from 'fs';
import * as path from 'path';

const ADMIN_URL = 'https://itqanway.com';
const ACADEMY_URL = 'https://e2e-test.itqanway.com';

async function createAuthPage(browser: Browser, role: string): Promise<{ page: Page; close: () => Promise<void> }> {
  const statePath = path.join(process.cwd(), `e2e/auth/${role}.json`);
  const ctx = await browser.newContext(fs.existsSync(statePath) ? { storageState: statePath } : {});
  const page = await ctx.newPage();
  return { page, close: () => ctx.close() };
}

test.describe('Cross-Role User Data Consistency', () => {
  test('admin can view student profiles', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'superadmin');
    await page.goto(`${ADMIN_URL}/admin/student-profiles`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
    await close();
  });

  test('supervisor can view managed teachers', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'supervisor');
    await page.goto(`${ACADEMY_URL}/supervisor-panel/managed-teachers`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
    await close();
  });

  test('admin can view teacher profiles', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'superadmin');
    await page.goto(`${ADMIN_URL}/admin/quran-teacher-profiles`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
    await close();
  });

  test('student profile data is accessible', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'student');
    await page.goto(`${ACADEMY_URL}/profile`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    const body = await page.textContent('body');
    expect(body?.length).toBeGreaterThan(100);
    await close();
  });
});
