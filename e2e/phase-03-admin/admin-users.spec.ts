import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertTableLoaded, waitForLivewire, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';

const ADMIN = 'https://itqanway.com/admin';

test.describe('Admin - User Management', () => {
  test.describe('Users Resource', () => {
    test('users list page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/users`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });

    test('users table has data rows', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/users`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const rows = page.locator('table tbody tr, .fi-ta-row');
      await expect(rows.first()).toBeVisible({ timeout: 15000 });
    });

    test('users table has search functionality', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/users`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const search = page.locator('input[type="search"], .fi-ta-search-field input, input[placeholder*="بحث"], input[placeholder*="search"]').first();
      await expect(search).toBeVisible({ timeout: 10000 });
    });
  });

  test.describe('Students Resource', () => {
    test('students list page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/student-profiles`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });

    test('students table has data', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/student-profiles`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const rows = page.locator('table tbody tr, .fi-ta-row');
      await expect(rows.first()).toBeVisible({ timeout: 15000 });
    });

    test('student detail/edit page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/student-profiles`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const firstRow = page.locator('table tbody tr a, .fi-ta-row a').first();
      if (await firstRow.isVisible({ timeout: 5000 }).catch(() => false)) {
        await firstRow.click();
        await page.waitForLoadState('networkidle');
        await assertNoServerError(page);
      }
    });
  });

  test.describe('Quran Teachers Resource', () => {
    test('quran teachers list page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/quran-teacher-profiles`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });
  });

  test.describe('Academic Teachers Resource', () => {
    test('academic teachers list page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academic-teacher-profiles`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });
  });

  test.describe('Parents Resource', () => {
    test('parents list page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/parent-profiles`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Supervisors Resource', () => {
    test('supervisors list page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/supervisor-profiles`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Admins Resource', () => {
    test('admins list page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/admins`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });
});
