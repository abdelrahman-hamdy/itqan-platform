import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertTableLoaded, waitForLivewire, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';

const ADMIN = 'https://itqanway.com/admin';

test.describe('Admin - Financial Management', () => {
  test.describe('Payments', () => {
    test('payments list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/payments`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });

    test('payments table has search', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/payments`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const search = page.locator('input[type="search"], .fi-ta-search-field input').first();
      await expect(search).toBeVisible({ timeout: 10000 });
    });

    test('payment detail loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/payments`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const firstLink = page.locator('table tbody tr a, .fi-ta-row a').first();
      if (await firstLink.isVisible({ timeout: 5000 }).catch(() => false)) {
        await firstLink.click();
        await page.waitForLoadState('networkidle');
        await assertNoServerError(page);
      }
    });
  });

  test.describe('Teacher Earnings', () => {
    test('teacher earnings page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/teacher-earnings`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Certificates', () => {
    test('certificates list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/certificates`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Saved Payment Methods', () => {
    test('saved payment methods list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/saved-payment-methods`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });
});
