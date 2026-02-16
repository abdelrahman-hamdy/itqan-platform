import { expect } from '@playwright/test';
import { academicTeacherTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://itqan-academy.itqanway.com/academic-teacher-panel';

test.describe('Academic Teacher - Additional Resources', () => {
  test('interactive course sessions page loads', async ({ academicTeacherPage: page }) => {
    await page.goto(`${BASE}/interactive-course-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('quizzes page loads', async ({ academicTeacherPage: page }) => {
    await page.goto(`${BASE}/quizzes`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('session recordings page loads', async ({ academicTeacherPage: page }) => {
    await page.goto(`${BASE}/session-recordings`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('interactive session reports page loads', async ({ academicTeacherPage: page }) => {
    await page.goto(`${BASE}/interactive-session-reports`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });
});
