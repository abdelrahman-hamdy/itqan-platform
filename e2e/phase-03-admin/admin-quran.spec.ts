import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertTableLoaded, waitForLivewire, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';

const ADMIN = 'https://itqanway.com/admin';

test.describe('Admin - Quran Management', () => {
  test.describe('Quran Packages', () => {
    test('quran packages list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/quran-packages`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });
  });

  test.describe('Quran Individual Circles', () => {
    test('quran individual circles list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/quran-individual-circles`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });

    test('quran individual circle detail loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/quran-individual-circles`);
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

  test.describe('Quran Group Circles', () => {
    test('quran circles list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/quran-circles`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Quran Sessions', () => {
    test('quran sessions list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/quran-sessions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });

    test('quran sessions table has search', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/quran-sessions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const search = page.locator('input[type="search"], .fi-ta-search-field input').first();
      await expect(search).toBeVisible({ timeout: 10000 });
    });

    test('quran session detail loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/quran-sessions`);
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

  test.describe('Quran Subscriptions', () => {
    test('quran subscriptions list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/quran-subscriptions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });
  });

  test.describe('Quran Trial Requests', () => {
    test('trial requests list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/quran-trial-requests`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Quran Reports', () => {
    test('quran session reports list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/student-session-reports`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });
});
