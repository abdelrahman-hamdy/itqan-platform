import { test, expect, Browser, Page } from '@playwright/test';
import { assertNoServerError } from '../fixtures/filament.fixture';
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

test.describe('Access Control', () => {
  test.describe('Panel Access Separation', () => {
    test('student cannot access admin panel', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'student');
      await page.goto(`${ADMIN_URL}/admin`);
      await page.waitForLoadState('networkidle');
      const url = page.url();
      const content = await page.content();
      // Should redirect to login, show 403, or show forbidden page
      const blocked = url.includes('login') || url.includes('403') ||
        content.includes('403') || content.includes('Forbidden') ||
        content.includes('غير مصرح');
      expect(blocked).toBeTruthy();
      await close();
    });

    test('student cannot access teacher panel', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'student');
      await page.goto(`${ACADEMY_URL}/teacher-panel`);
      await page.waitForLoadState('networkidle');
      const url = page.url();
      const content = await page.content();
      const blocked = url.includes('login') || url.includes('403') ||
        content.includes('403') || content.includes('Forbidden') ||
        content.includes('غير مصرح');
      expect(blocked).toBeTruthy();
      await close();
    });

    test('student cannot access supervisor panel', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'student');
      await page.goto(`${ACADEMY_URL}/supervisor-panel`);
      await page.waitForLoadState('networkidle');
      const url = page.url();
      const content = await page.content();
      const blocked = url.includes('login') || url.includes('403') ||
        content.includes('403') || content.includes('Forbidden') ||
        content.includes('غير مصرح');
      expect(blocked).toBeTruthy();
      await close();
    });

    test('teacher cannot access admin panel', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'quran-teacher');
      await page.goto(`${ADMIN_URL}/admin`);
      await page.waitForLoadState('networkidle');
      const url = page.url();
      const content = await page.content();
      const blocked = url.includes('login') || url.includes('403') ||
        content.includes('403') || content.includes('Forbidden') ||
        content.includes('غير مصرح');
      expect(blocked).toBeTruthy();
      await close();
    });

    test('teacher cannot access supervisor panel', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'quran-teacher');
      await page.goto(`${ACADEMY_URL}/supervisor-panel`);
      await page.waitForLoadState('networkidle');
      const url = page.url();
      const content = await page.content();
      const blocked = url.includes('login') || url.includes('403') ||
        content.includes('403') || content.includes('Forbidden') ||
        content.includes('غير مصرح');
      expect(blocked).toBeTruthy();
      await close();
    });

    test('supervisor cannot access admin panel', async ({ browser }) => {
      const { page, close } = await createAuthPage(browser, 'supervisor');
      await page.goto(`${ADMIN_URL}/admin`);
      await page.waitForLoadState('networkidle');
      const url = page.url();
      const content = await page.content();
      const blocked = url.includes('login') || url.includes('403') ||
        content.includes('403') || content.includes('Forbidden') ||
        content.includes('غير مصرح');
      expect(blocked).toBeTruthy();
      await close();
    });
  });

  test.describe('Unauthenticated Access', () => {
    test('unauthenticated user cannot access student routes', async ({ page }) => {
      await page.context().clearCookies();
      await page.goto(`${ACADEMY_URL}/profile`);
      await page.waitForLoadState('networkidle');
      expect(page.url()).toContain('/login');
    });

    test('unauthenticated user cannot access admin panel', async ({ page }) => {
      await page.context().clearCookies();
      await page.goto(`${ADMIN_URL}/admin`);
      await page.waitForLoadState('networkidle');
      expect(page.url()).toContain('/login');
    });

    test('unauthenticated user cannot access chat', async ({ page }) => {
      await page.context().clearCookies();
      await page.goto(`${ACADEMY_URL}/chats`);
      await page.waitForLoadState('networkidle');
      expect(page.url()).toContain('/login');
    });
  });
});
