import { expect } from '@playwright/test';
import { teacherTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, waitForLivewire, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const ACADEMY_BASE = 'https://e2e-test.itqanway.com';
const PANEL_BASE = 'https://e2e-test.itqanway.com/teacher-panel';

test.describe('Teacher - Homework Management', () => {
  test('homework web route loads', async ({ teacherPage: page }) => {
    await assertPageLoads(page, `${ACADEMY_BASE}/teacher/homework`);
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('quizzes page loads in teacher panel', async ({ teacherPage: page }) => {
    await page.goto(`${PANEL_BASE}/quizzes`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
  });

  test('quiz assignments page loads in teacher panel', async ({ teacherPage: page }) => {
    await page.goto(`${PANEL_BASE}/quiz-assignments`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await waitForLivewire(page);
    await assertNoServerError(page);
  });
});
