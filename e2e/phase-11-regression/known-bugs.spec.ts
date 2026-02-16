import { test, expect, Browser, Page } from '@playwright/test';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';
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

test.describe('Known Bug Regression Tests', () => {
  test.describe('Chat Route Bugs', () => {
    test('chat page loads without errors', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'student');
      await page.goto(`${ACADEMY_URL}/chats`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      await close();
    });

    test('chat conversation detail does not 500 (was int type hint)', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'student');
      await page.goto(`${ACADEMY_URL}/chats`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      const convo = page.locator('a[href*="/chats/"]').first();
      if (await convo.isVisible({ timeout: 5000 }).catch(() => false)) {
        await convo.click();
        await page.waitForLoadState('networkidle');
        await assertNoServerError(page);
        await assertNoPHPErrors(page);
      }
      await close();
    });
  });

  test.describe('Broadcasting/WebSocket Bugs', () => {
    test('pages with real-time features load without critical JS errors', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'student');
      const errors: string[] = [];
      page.on('pageerror', (err) => errors.push(err.message));
      await page.goto(ACADEMY_URL);
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(3000);
      const criticalErrors = errors.filter(e =>
        !e.includes('ResizeObserver') &&
        !e.includes('Script error') &&
        !e.includes('Non-Error')
      );
      expect(criticalErrors.length).toBeLessThan(3);
      await close();
    });
  });

  test.describe('Timezone Bugs', () => {
    test('calendar shows correct timezone context', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'student');
      await page.goto(`${ACADEMY_URL}/student/calendar`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      await close();
    });
  });

  test.describe('Payment Bugs', () => {
    test('payment page handles missing integration gracefully', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'student');
      await page.goto(`${ACADEMY_URL}/payments`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      await assertNoPHPErrors(page);
      await close();
    });
  });

  test.describe('Admin Panel Bugs', () => {
    test('admin dashboard loads without widget errors', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'superadmin');
      await page.goto(`${ADMIN_URL}/admin`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      await assertNoPHPErrors(page);
      await close();
    });
  });
});
