import { expect } from '@playwright/test';
import { parentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertRTL, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Parent Dashboard', () => {
  test('parent dashboard loads', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('parent dashboard has RTL layout', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertRTL(page);
  });

  test('parent profile page loads', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent/profile`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });
});
