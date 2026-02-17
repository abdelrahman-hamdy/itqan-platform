import { expect } from '@playwright/test';
import { teacherTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire, assertTableHasData, openTableFilters, assertTableSearch } from '../fixtures/filament.fixture';
import { assertMeaningfulContent } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com/teacher-panel';

test.describe('Quran Teacher Panel - Interactive Tests', () => {
  test('quran sessions table has filters', async ({ teacherPage: page }) => {
    await page.goto(`${BASE}/quran-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);
    await assertTableHasData(page);
    await openTableFilters(page);
  });

  test('individual circles table loads', async ({ teacherPage: page }) => {
    await page.goto(`${BASE}/quran-individual-circles`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);
    await assertTableHasData(page);
  });

  test('student session reports table loads', async ({ teacherPage: page }) => {
    await page.goto(`${BASE}/student-session-reports`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);
  });
});
