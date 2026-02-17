import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Student - Subscriptions', () => {
  test('subscriptions page loads', async ({ studentPage: page }) => {
    await assertPageLoads(page, `${BASE}/subscriptions`);
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('subscriptions page has no PHP errors', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/subscriptions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoPHPErrors(page);
  });

  test('subscription detail loads when subscription exists', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/subscriptions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    // Look for links to individual subscriptions
    const subLink = page.locator('a[href*="/subscriptions/"], a[href*="/academic-subscriptions/"]').first();
    if (await subLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await subLink.click();
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    }
  });

  test('subscription page shows content (list or empty state)', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/subscriptions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    const body = await page.textContent('body');
    expect(body?.length).toBeGreaterThan(100);
  });
});
