import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads } from '../fixtures/helpers';

const BASE = 'https://itqan-academy.itqanway.com';

test.describe('Student - Certificates', () => {
  test('certificates page loads', async ({ studentPage: page }) => {
    await assertPageLoads(page, `${BASE}/certificates`);
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('certificates page shows list or empty state', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/certificates`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    const body = await page.textContent('body');
    expect(body?.length).toBeGreaterThan(10);
  });
});
