import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://itqan-academy.itqanway.com';

// Note: Students don't have session listing pages. They access sessions
// through their subscriptions page. These tests verify navigation from subscriptions.

test.describe('Student - Sessions', () => {
  test.describe('Session Access via Subscriptions', () => {
    test('subscriptions page shows sessions/links', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/subscriptions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      const body = await page.textContent('body');
      expect(body?.length).toBeGreaterThan(100);
    });

    test('quran session detail loads when navigated from subscription', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/subscriptions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      // Look for links to sessions or subscriptions detail
      const sessionLink = page.locator('a[href*="/sessions/"]').first();
      if (await sessionLink.isVisible({ timeout: 5000 }).catch(() => false)) {
        await sessionLink.click();
        await page.waitForLoadState('networkidle');
        await assertNotLoginPage(page);
        await assertNoServerError(page);
        await assertNoPHPErrors(page);
      }
    });

    test('academic session detail loads when navigated', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/subscriptions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      const sessionLink = page.locator('a[href*="/academic-sessions/"]').first();
      if (await sessionLink.isVisible({ timeout: 5000 }).catch(() => false)) {
        await sessionLink.click();
        await page.waitForLoadState('networkidle');
        await assertNotLoginPage(page);
        await assertNoServerError(page);
      }
    });
  });

  test.describe('Interactive Course Sessions', () => {
    test('interactive courses page lists available courses', async ({ studentPage: page }) => {
      await assertPageLoads(page, `${BASE}/interactive-courses`);
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });
  });
});
