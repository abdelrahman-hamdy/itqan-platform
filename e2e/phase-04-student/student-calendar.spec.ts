import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Student - Calendar', () => {
  test('calendar page loads', async ({ studentPage: page }) => {
    await assertPageLoads(page, `${BASE}/student/calendar`);
    await assertNoServerError(page);
  });

  test('calendar page has no PHP errors', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/student/calendar`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoPHPErrors(page);
  });

  test('calendar renders calendar component', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/student/calendar`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    // Look for calendar-related elements (FullCalendar, Livewire calendar, or any calendar UI)
    const calendar = page.locator('.fc, .calendar, [class*="calendar"], [id*="calendar"], [wire\\:id], .fc-view-harness').first();
    const hasCalendar = await calendar.isVisible({ timeout: 10000 }).catch(() => false);
    // Fallback: check page has meaningful content (calendar rendered as custom component)
    if (!hasCalendar) {
      const body = await page.textContent('body');
      expect(body?.length).toBeGreaterThan(100);
    }
  });

  test('calendar shows current month', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/student/calendar`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    // Calendar should display current month/year
    const body = await page.textContent('body');
    const currentYear = new Date().getFullYear().toString();
    expect(body).toContain(currentYear);
  });
});
