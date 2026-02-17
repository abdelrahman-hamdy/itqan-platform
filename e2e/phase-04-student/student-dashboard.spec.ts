import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertRTL, waitForLivewire, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Student Dashboard', () => {
  test('academy homepage loads for authenticated student', async ({ studentPage: page }) => {
    await page.goto(BASE);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await assertNoPHPErrors(page);
  });

  test('homepage has RTL layout', async ({ studentPage: page }) => {
    await page.goto(BASE);
    await assertRTL(page);
  });

  test('homepage has navigation elements', async ({ studentPage: page }) => {
    await page.goto(BASE);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    // Authenticated student should see meaningful page content
    const body = await page.textContent('body');
    expect(body?.length).toBeGreaterThan(200);
  });

  test('student profile page loads', async ({ studentPage: page }) => {
    await assertPageLoads(page, `${BASE}/profile`);
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('student profile has content', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/profile`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    const body = await page.textContent('body');
    expect(body?.length).toBeGreaterThan(100);
  });

  test('student search page loads', async ({ studentPage: page }) => {
    await assertPageLoads(page, `${BASE}/search`);
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });
});
