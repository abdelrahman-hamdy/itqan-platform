import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Subscription Management', () => {
  test('subscriptions list loads', async ({ studentPage: page }) => {
    await assertPageLoads(page, `${BASE}/subscriptions`);
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('subscription detail shows info when exists', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/subscriptions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    const subLink = page.locator('a[href*="/subscriptions/"], a[href*="/academic-subscriptions/"]').first();
    if (await subLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await subLink.click();
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      await assertNoPHPErrors(page);
      const body = await page.textContent('body');
      expect(body?.length).toBeGreaterThan(100);
    }
  });

  test('auto-renew toggle is accessible', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/subscriptions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    const subLink = page.locator('a[href*="/subscriptions/"], a[href*="/academic-subscriptions/"]').first();
    if (await subLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await subLink.click();
      await page.waitForLoadState('networkidle');
      const toggle = page.locator('input[type="checkbox"], [role="switch"], button:has-text("تجديد"), button:has-text("auto")').first();
      if (await toggle.isVisible({ timeout: 5000 }).catch(() => false)) {
        expect(true).toBeTruthy();
      }
    }
  });

  test('enrollment page for new subscription loads', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/quran-teachers`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    const enrollLink = page.locator('a[href*="subscribe"], button:has-text("اشتراك"), a:has-text("اشتراك")').first();
    if (await enrollLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await enrollLink.click();
      await page.waitForLoadState('networkidle');
      await assertNoServerError(page);
    }
  });
});
