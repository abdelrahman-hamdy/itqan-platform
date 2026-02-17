import { test, expect, Browser, Page } from '@playwright/test';
import { assertNoServerError, waitForLivewire, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';
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

test.describe('Edge Cases', () => {
  test.describe('404 Pages', () => {
    test('non-existent page returns proper 404', async ({ page }) => {
      const response = await page.goto(`${ACADEMY_URL}/this-page-does-not-exist-12345`);
      expect(response?.status()).toBe(404);
    });

    test('404 page has proper styling (not raw error)', async ({ page }) => {
      await page.goto(`${ACADEMY_URL}/this-page-does-not-exist-12345`);
      await page.waitForLoadState('networkidle');
      const body = await page.textContent('body');
      expect(body?.length).toBeGreaterThan(50);
    });

    test('non-existent admin resource returns proper error', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'superadmin');
      const response = await page.goto(`${ADMIN_URL}/admin/nonexistent-resource`);
      expect(response?.status()).toBe(404);
      await close();
    });
  });

  test.describe('Deleted/Invalid Resources', () => {
    test('invalid student ID in admin does not crash', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'superadmin');
      const response = await page.goto(`${ADMIN_URL}/admin/student-profiles/999999999/edit`);
      expect(response?.status()).not.toBe(500);
      await close();
    });

    test('invalid session ID does not crash for student', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'student');
      const response = await page.goto(`${ACADEMY_URL}/sessions/invalid-uuid-12345`);
      expect(response?.status()).not.toBe(500);
      await close();
    });
  });

  test.describe('CSRF Protection', () => {
    test('POST to login without CSRF token fails gracefully', async ({ page }) => {
      const response = await page.request.post(`${ACADEMY_URL}/login`, {
        data: { email: 'test@test.com', password: 'test' },
      });
      expect(response.status()).not.toBe(500);
    });
  });

  test.describe('Empty States', () => {
    test('student homepage handles no data gracefully', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'student');
      await page.goto(ACADEMY_URL);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      await assertNoPHPErrors(page);
      await close();
    });

    test('empty search results do not crash', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'superadmin');
      await page.goto(`${ADMIN_URL}/admin/users`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const search = page.locator('input[type="search"], .fi-ta-search-field input').first();
      if (await search.isVisible({ timeout: 5000 }).catch(() => false)) {
        await search.fill('zzzznonexistentuser99999');
        await page.waitForTimeout(1000);
        await assertNoServerError(page);
      }
      await close();
    });
  });

  test.describe('Special Characters', () => {
    test('search with Arabic characters works', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'superadmin');
      await page.goto(`${ADMIN_URL}/admin/users`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const search = page.locator('input[type="search"], .fi-ta-search-field input').first();
      if (await search.isVisible({ timeout: 5000 }).catch(() => false)) {
        await search.fill('عبدالرحمن');
        await page.waitForTimeout(1000);
        await assertNoServerError(page);
      }
      await close();
    });

    test('search with special characters does not crash', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'superadmin');
      await page.goto(`${ADMIN_URL}/admin/users`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const search = page.locator('input[type="search"], .fi-ta-search-field input').first();
      if (await search.isVisible({ timeout: 5000 }).catch(() => false)) {
        await search.fill('<script>alert("xss")</script>');
        await page.waitForTimeout(1000);
        await assertNoServerError(page);
      }
      await close();
    });
  });

  test.describe('Concurrent Access', () => {
    test('multiple pages can load simultaneously', async ({ browser }) => {
      const { page: page1, close: close1 } = await createAuthPage(browser, 'student');
      const { page: page2, close: close2 } = await createAuthPage(browser, 'student');
      const { page: page3, close: close3 } = await createAuthPage(browser, 'student');

      await Promise.all([
        page1.goto(ACADEMY_URL),
        page2.goto(`${ACADEMY_URL}/subscriptions`),
        page3.goto(`${ACADEMY_URL}/profile`),
      ]);

      for (const page of [page1, page2, page3]) {
        await assertNoServerError(page);
      }

      await close1();
      await close2();
      await close3();
    });
  });
});
