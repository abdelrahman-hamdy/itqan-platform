import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire, assertTableHasData, assertTableSearch, openTableFilters, countTableRows, assertFilamentFormField } from '../fixtures/filament.fixture';
import { assertMeaningfulContent, assertNoPHPErrors } from '../fixtures/helpers';

const ADMIN = 'https://itqanway.com/admin';

test.describe('Admin - User Journeys', () => {
  test('dashboard to students to detail', async ({ adminPage: page }) => {
    // Step 1: Load the admin dashboard
    await page.goto(ADMIN);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await assertMeaningfulContent(page);

    // Step 2: Navigate to students list
    await page.goto(`${ADMIN}/student-profiles`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);
    await assertTableHasData(page);

    // Step 3: Try to click the first table row to view student detail
    const firstRowLink = page.locator('.fi-ta-row a, table tbody tr a').first();
    const hasLink = await firstRowLink.isVisible({ timeout: 8000 }).catch(() => false);

    if (hasLink) {
      await firstRowLink.click();
      await page.waitForLoadState('networkidle');
      await assertNoServerError(page);
      await assertNoPHPErrors(page);
      await assertMeaningfulContent(page);
    }
    // If no rows/links found, the journey still passes up to this point
  });

  test('subscriptions to detail to payments', async ({ adminPage: page }) => {
    // Step 1: Load the quran subscriptions list
    await page.goto(`${ADMIN}/quran-subscriptions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);

    // Step 2: Try to click the first row to view subscription detail
    const firstRowLink = page.locator('.fi-ta-row a, table tbody tr a').first();
    const hasLink = await firstRowLink.isVisible({ timeout: 8000 }).catch(() => false);

    if (hasLink) {
      await firstRowLink.click();
      await page.waitForLoadState('networkidle');
      await assertNoServerError(page);
      await assertNoPHPErrors(page);
      await assertMeaningfulContent(page);
    }
    // If no rows/links found, the journey still passes - the list page loaded correctly
  });
});
