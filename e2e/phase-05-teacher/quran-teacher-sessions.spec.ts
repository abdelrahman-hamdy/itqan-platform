import { expect } from '@playwright/test';
import { teacherTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, waitForLivewire, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const ACADEMY_BASE = 'https://itqan-academy.itqanway.com';

test.describe('Quran Teacher - Web Routes', () => {
  test.describe('Teacher Individual Circles', () => {
    test('teacher individual circles page loads', async ({ teacherPage: page }) => {
      await assertPageLoads(page, `${ACADEMY_BASE}/teacher/individual-circles`);
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Teacher Group Circles', () => {
    test('teacher group circles page loads', async ({ teacherPage: page }) => {
      await assertPageLoads(page, `${ACADEMY_BASE}/teacher/group-circles`);
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Teacher Homework Web Route', () => {
    test('teacher homework page loads', async ({ teacherPage: page }) => {
      await assertPageLoads(page, `${ACADEMY_BASE}/teacher/homework`);
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });

    test('teacher homework statistics page loads', async ({ teacherPage: page }) => {
      await assertPageLoads(page, `${ACADEMY_BASE}/teacher/homework/statistics`);
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });
  });
});
