import { expect } from '@playwright/test';
import { academicTeacherTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, waitForLivewire, assertRTL, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com/academic-teacher-panel';

test.describe('Academic Teacher Panel', () => {
  test.describe('Dashboard', () => {
    test('academic teacher dashboard loads', async ({ academicTeacherPage: page }) => {
      await page.goto(BASE);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('academic teacher dashboard has RTL layout', async ({ academicTeacherPage: page }) => {
      await page.goto(BASE);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertRTL(page);
    });
  });

  test.describe('Panel Resources', () => {
    test('academic sessions page loads', async ({ academicTeacherPage: page }) => {
      await page.goto(`${BASE}/academic-sessions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });

    test('academic individual lessons page loads', async ({ academicTeacherPage: page }) => {
      await page.goto(`${BASE}/academic-individual-lessons`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });

    test('interactive courses page loads', async ({ academicTeacherPage: page }) => {
      await page.goto(`${BASE}/interactive-courses`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });

    test('quiz assignments page loads', async ({ academicTeacherPage: page }) => {
      await page.goto(`${BASE}/quiz-assignments`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });

    test('academic session reports page loads', async ({ academicTeacherPage: page }) => {
      await page.goto(`${BASE}/academic-session-reports`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });

    test('certificates page loads', async ({ academicTeacherPage: page }) => {
      await page.goto(`${BASE}/certificates`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });

    test('teacher earnings page loads', async ({ academicTeacherPage: page }) => {
      await page.goto(`${BASE}/teacher-earnings`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });

    test('homework submissions page loads', async ({ academicTeacherPage: page }) => {
      await page.goto(`${BASE}/homework-submissions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });
  });
});
