import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Student - Browse Content', () => {
  test.describe('Browse Teachers', () => {
    test('quran teachers listing loads for student', async ({ studentPage: page }) => {
      await assertPageLoads(page, `${BASE}/quran-teachers`);
      await assertNoServerError(page);
    });

    test('academic teachers listing loads for student', async ({ studentPage: page }) => {
      await assertPageLoads(page, `${BASE}/academic-teachers`);
      await assertNoServerError(page);
    });

    test('teacher detail page loads', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/quran-teachers`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      const teacherLink = page.locator('a[href*="/quran-teachers/"]').first();
      if (await teacherLink.isVisible({ timeout: 5000 }).catch(() => false)) {
        await teacherLink.click();
        await page.waitForLoadState('networkidle');
        await assertNotLoginPage(page);
        await assertNoServerError(page);
      }
    });
  });

  test.describe('Browse Circles', () => {
    test('quran circles listing loads', async ({ studentPage: page }) => {
      await assertPageLoads(page, `${BASE}/quran-circles`);
      await assertNoServerError(page);
    });
  });

  test.describe('Browse Courses', () => {
    test('interactive courses listing loads', async ({ studentPage: page }) => {
      await assertPageLoads(page, `${BASE}/interactive-courses`);
      await assertNoServerError(page);
    });

    test('recorded courses listing loads', async ({ studentPage: page }) => {
      await assertPageLoads(page, `${BASE}/courses`);
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
});
