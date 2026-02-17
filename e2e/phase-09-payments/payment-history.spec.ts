import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Payment History', () => {
  test('payments page loads for student', async ({ studentPage: page }) => {
    await assertPageLoads(page, `${BASE}/payments`);
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('payments page has no PHP errors', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/payments`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoPHPErrors(page);
  });

  test('payments page shows list or empty state', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/payments`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    const body = await page.textContent('body');
    expect(body?.length).toBeGreaterThan(50);
  });

  test('payment detail loads when payment exists', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/payments`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    const paymentLink = page.locator('a[href*="/payments/"]').first();
    if (await paymentLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await paymentLink.click();
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    }
  });
});
