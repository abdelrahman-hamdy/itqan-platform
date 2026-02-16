import { expect } from '@playwright/test';
import { supervisorTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertTableLoaded, waitForLivewire, assertRTL, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://itqan-academy.itqanway.com/supervisor-panel';

test.describe('Supervisor Panel', () => {
  test.describe('Dashboard', () => {
    test('supervisor dashboard loads', async ({ supervisorPage: page }) => {
      await page.goto(BASE);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      await assertNoPHPErrors(page);
    });

    test('supervisor dashboard has RTL layout', async ({ supervisorPage: page }) => {
      await page.goto(BASE);
      await assertRTL(page);
    });

    test('supervisor dashboard has widgets', async ({ supervisorPage: page }) => {
      await page.goto(BASE);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      const widgets = page.locator('.fi-wi-stats-overview, .fi-widget, [wire\\:id]');
      await expect(widgets.first()).toBeVisible({ timeout: 15000 });
    });

    test('supervisor sidebar is visible', async ({ supervisorPage: page }) => {
      await page.goto(BASE);
      await expect(page.locator('.fi-sidebar, nav, aside').first()).toBeVisible();
    });
  });

  test.describe('Teachers Management', () => {
    test('managed teachers page loads', async ({ supervisorPage: page }) => {
      await page.goto(`${BASE}/managed-teachers`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('managed teacher reviews page loads', async ({ supervisorPage: page }) => {
      await page.goto(`${BASE}/managed-teacher-reviews`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('managed teacher earnings page loads', async ({ supervisorPage: page }) => {
      await page.goto(`${BASE}/managed-teacher-earnings`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Sessions Monitoring', () => {
    test('monitored all sessions page loads', async ({ supervisorPage: page }) => {
      await page.goto(`${BASE}/monitored-all-sessions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Circles & Lessons', () => {
    test('monitored individual circles page loads', async ({ supervisorPage: page }) => {
      await page.goto(`${BASE}/monitored-individual-circles`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('monitored group circles page loads', async ({ supervisorPage: page }) => {
      await page.goto(`${BASE}/monitored-group-circles`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('monitored academic lessons page loads', async ({ supervisorPage: page }) => {
      await page.goto(`${BASE}/monitored-academic-lessons`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('monitored interactive courses page loads', async ({ supervisorPage: page }) => {
      await page.goto(`${BASE}/monitored-interactive-courses`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Reports & Monitoring', () => {
    test('monitored session reports page loads', async ({ supervisorPage: page }) => {
      await page.goto(`${BASE}/monitored-session-reports`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('monitored certificates page loads', async ({ supervisorPage: page }) => {
      await page.goto(`${BASE}/monitored-certificates`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('monitored quiz assignments page loads', async ({ supervisorPage: page }) => {
      await page.goto(`${BASE}/monitored-quiz-assignments`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });

    test('monitored trial requests page loads', async ({ supervisorPage: page }) => {
      await page.goto(`${BASE}/monitored-trial-requests`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });
});
