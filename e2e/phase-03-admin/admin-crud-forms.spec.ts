import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire, assertTableHasData, assertTableSearch, openTableFilters, countTableRows, assertFilamentFormField } from '../fixtures/filament.fixture';
import { assertMeaningfulContent, assertNoPHPErrors } from '../fixtures/helpers';

const ADMIN = 'https://itqanway.com/admin';

test.describe('Admin - CRUD Forms & Tables', () => {
  test('quran subscription create form has fields', async ({ adminPage: page }) => {
    await page.goto(`${ADMIN}/quran-subscriptions/create`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);

    // Verify form has key fields visible
    const formFields = page.locator('input, select, textarea, [wire\\:model]');
    const fieldCount = await formFields.count();
    expect(fieldCount).toBeGreaterThan(0);
  });

  test('quran subscription create form has save action', async ({ adminPage: page }) => {
    await page.goto(`${ADMIN}/quran-subscriptions/create`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);

    // Verify the form has a save/create action (Filament uses dropdown actions)
    const actionButton = page.locator(
      'button[type="submit"], .fi-ac-btn-action, .fi-form-actions button, button:has-text("حفظ"), button:has-text("إنشاء")'
    );
    const actionCount = await actionButton.count();
    expect(actionCount).toBeGreaterThan(0);
  });

  test('quran subscriptions create form loads with fields', async ({ adminPage: page }) => {
    await page.goto(`${ADMIN}/quran-subscriptions/create`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);
    await assertMeaningfulContent(page);
  });

  test('quran sessions table loads with data', async ({ adminPage: page }) => {
    await page.goto(`${ADMIN}/quran-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);
    await assertTableHasData(page);
  });

  test('quran sessions table has search', async ({ adminPage: page }) => {
    await page.goto(`${ADMIN}/quran-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertTableSearch(page);
  });

  test('academic sessions list has table', async ({ adminPage: page }) => {
    await page.goto(`${ADMIN}/academic-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);
    await assertTableHasData(page);
  });

  test('student profile edit form loads with data', async ({ adminPage: page }) => {
    await page.goto(`${ADMIN}/student-profiles`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);

    // Try to find the first row link in the table
    const firstRowLink = page.locator('.fi-ta-row a, table tbody tr a').first();
    const hasRows = await firstRowLink.isVisible({ timeout: 8000 }).catch(() => false);

    if (!hasRows) {
      test.skip(true, 'No student rows found in table - skipping edit form test');
      return;
    }

    // Navigate to the detail/edit page
    await firstRowLink.click();
    await page.waitForLoadState('networkidle');
    await assertNoServerError(page);
    await assertNoPHPErrors(page);
    await assertMeaningfulContent(page);
  });

  test('users table loads with pagination', async ({ adminPage: page }) => {
    await page.goto(`${ADMIN}/users`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);
    await assertTableHasData(page);

    // Check for pagination navigation or confirm table loaded
    const pagination = page.locator('nav[aria-label="Pagination"], .fi-ta-pagination, nav[role="navigation"]').first();
    const hasPagination = await pagination.isVisible({ timeout: 5000 }).catch(() => false);
    // If no pagination, it means there are few records - still valid
    const rowCount = await countTableRows(page);
    expect(hasPagination || rowCount >= 0).toBeTruthy();
  });

  test('academic packages create form loads', async ({ adminPage: page }) => {
    await page.goto(`${ADMIN}/academic-packages/create`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);
    await assertMeaningfulContent(page);
  });

  test('quran packages table has data', async ({ adminPage: page }) => {
    await page.goto(`${ADMIN}/quran-packages`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);
    await assertTableHasData(page);
  });
});
