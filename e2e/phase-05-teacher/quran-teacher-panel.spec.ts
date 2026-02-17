import { expect } from '@playwright/test';
import { teacherTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertTableLoaded, waitForLivewire, assertRTL, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com/teacher-panel';

test.describe('Quran Teacher Panel', () => {
  test.describe('Dashboard', () => {
    test('teacher dashboard loads', async ({ teacherPage: page }) => {
      await page.goto(BASE);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      await assertNoPHPErrors(page);
    });

    test('teacher dashboard has RTL layout', async ({ teacherPage: page }) => {
      await page.goto(BASE);
      await assertRTL(page);
    });

    test('teacher dashboard has widgets', async ({ teacherPage: page }) => {
      await page.goto(BASE);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const widgets = page.locator('.fi-wi-stats-overview, .fi-widget, [wire\\:id]');
      await expect(widgets.first()).toBeVisible({ timeout: 15000 });
    });

    test('teacher sidebar is visible', async ({ teacherPage: page }) => {
      await page.goto(BASE);
      await expect(page.locator('.fi-sidebar, nav, aside').first()).toBeVisible();
    });
  });

  test.describe('Panel Resources', () => {
    test('quran individual circles page loads', async ({ teacherPage: page }) => {
      await page.goto(`${BASE}/quran-individual-circles`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('quran sessions page loads', async ({ teacherPage: page }) => {
      await page.goto(`${BASE}/quran-sessions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('student session reports page loads', async ({ teacherPage: page }) => {
      await page.goto(`${BASE}/student-session-reports`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('quran trial requests page loads', async ({ teacherPage: page }) => {
      await page.goto(`${BASE}/quran-trial-requests`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('quizzes page loads', async ({ teacherPage: page }) => {
      await page.goto(`${BASE}/quizzes`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('quiz assignments page loads', async ({ teacherPage: page }) => {
      await page.goto(`${BASE}/quiz-assignments`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('certificates page loads', async ({ teacherPage: page }) => {
      await page.goto(`${BASE}/certificates`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('teacher earnings page loads', async ({ teacherPage: page }) => {
      await page.goto(`${BASE}/teacher-earnings`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });
});
