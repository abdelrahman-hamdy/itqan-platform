import { test, expect, Browser, Page } from '@playwright/test';
import { assertArabicContent, assertNotLoginPage } from '../fixtures/filament.fixture';
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

test.describe('Translation Completeness', () => {
  test('academy homepage has Arabic content', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'student');
    await page.goto(ACADEMY_URL);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertArabicContent(page);
    await close();
  });

  test('admin dashboard has Arabic content', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'superadmin');
    await page.goto(`${ADMIN_URL}/admin`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertArabicContent(page);
    await close();
  });

  test('teacher panel has Arabic content', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'quran-teacher');
    await page.goto(`${ACADEMY_URL}/teacher-panel`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertArabicContent(page);
    await close();
  });

  test('supervisor panel has Arabic content', async ({ browser }) => {
    const { page, close } = await createAuthPage(browser, 'supervisor');
    await page.goto(`${ACADEMY_URL}/supervisor-panel`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertArabicContent(page);
    await close();
  });

  test('login page has Arabic content', async ({ page }) => {
    await page.goto(`${ACADEMY_URL}/login`);
    await page.waitForLoadState('networkidle');
    await assertArabicContent(page);
  });

  test('registration page has Arabic content', async ({ page }) => {
    await page.goto(`${ACADEMY_URL}/register`);
    await page.waitForLoadState('networkidle');
    await assertArabicContent(page);
  });
});
