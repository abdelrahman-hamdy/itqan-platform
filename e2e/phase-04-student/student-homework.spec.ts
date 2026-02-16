import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://itqan-academy.itqanway.com';

test.describe('Student - Homework', () => {
  test('homework list page loads', async ({ studentPage: page }) => {
    await assertPageLoads(page, `${BASE}/homework`);
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('homework page has no PHP errors', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/homework`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoPHPErrors(page);
  });

  test('homework detail page loads when homework exists', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/homework`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    const hwLink = page.locator('a[href*="/homework/"]').first();
    if (await hwLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await hwLink.click();
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    }
  });
});
