import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://itqan-academy.itqanway.com';

test.describe('Student - Courses', () => {
  test.describe('Course Catalog', () => {
    test('courses page loads', async ({ studentPage: page }) => {
      await assertPageLoads(page, `${BASE}/courses`);
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });

    test('course detail page loads', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/courses`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      const courseLink = page.locator('a[href*="/courses/"]').first();
      if (await courseLink.isVisible({ timeout: 5000 }).catch(() => false)) {
        await courseLink.click();
        await page.waitForLoadState('networkidle');
        await assertNotLoginPage(page);
        await assertNoServerError(page);
      }
    });
  });

  test.describe('Course Learning', () => {
    test('course learn page loads when enrolled', async ({ studentPage: page }) => {
      // Navigate to courses and look for a learn link
      await page.goto(`${BASE}/courses`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      const learnLink = page.locator('a[href*="/learn"]').first();
      if (await learnLink.isVisible({ timeout: 5000 }).catch(() => false)) {
        await learnLink.click();
        await page.waitForLoadState('networkidle');
        await assertNotLoginPage(page);
        await assertNoServerError(page);
        await assertNoPHPErrors(page);
      }
    });
  });
});
