import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertTableLoaded, waitForLivewire, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';

const ADMIN = 'https://itqanway.com/admin';

test.describe('Admin - Academic Management', () => {
  test.describe('Grade Levels', () => {
    test('grade levels list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academic-grade-levels`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Subjects', () => {
    test('subjects list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academic-subjects`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Academic Packages', () => {
    test('academic packages list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academic-packages`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });
  });

  test.describe('Academic Individual Lessons', () => {
    test('academic individual lessons list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academic-individual-lessons`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });

    test('academic lesson detail loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academic-individual-lessons`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const firstLink = page.locator('table tbody tr a, .fi-ta-row a').first();
      if (await firstLink.isVisible({ timeout: 5000 }).catch(() => false)) {
        await firstLink.click();
        await page.waitForLoadState('networkidle');
        await assertNoServerError(page);
      }
    });
  });

  test.describe('Academic Sessions', () => {
    test('academic sessions list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academic-sessions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });

    test('academic session detail loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academic-sessions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const firstLink = page.locator('table tbody tr a, .fi-ta-row a').first();
      if (await firstLink.isVisible({ timeout: 5000 }).catch(() => false)) {
        await firstLink.click();
        await page.waitForLoadState('networkidle');
        await assertNoServerError(page);
      }
    });
  });

  test.describe('Academic Subscriptions', () => {
    test('academic subscriptions list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academic-subscriptions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
      await assertTableLoaded(page);
    });
  });

  test.describe('Interactive Courses', () => {
    test('interactive courses list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/interactive-courses`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('interactive course sessions list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/interactive-course-sessions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Recorded Courses', () => {
    test('recorded courses list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/recorded-courses`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Academic Reports', () => {
    test('academic session reports list loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academic-session-reports`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });
});
