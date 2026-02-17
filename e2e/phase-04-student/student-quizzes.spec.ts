import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Student - Quizzes', () => {
  test('quizzes list page loads', async ({ studentPage: page }) => {
    await assertPageLoads(page, `${BASE}/quizzes`);
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('quizzes page has no PHP errors', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/quizzes`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoPHPErrors(page);
  });

  test('quiz detail page loads when quiz exists', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/quizzes`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    const quizLink = page.locator('a[href*="/quiz"], a[href*="/quizzes/"]').first();
    if (await quizLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await quizLink.click();
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    }
  });
});
