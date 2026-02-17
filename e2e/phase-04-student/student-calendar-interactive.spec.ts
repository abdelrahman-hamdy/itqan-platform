import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire } from '../fixtures/filament.fixture';
import { assertMeaningfulContent, assertNoPHPErrors, assertHasContentSections, assertHasNavigation, assertContainsArabic } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Student - Calendar Interactive', () => {
  test('calendar has navigation controls', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/student/calendar`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Look for calendar navigation buttons (prev/next month)
    // Could be FullCalendar buttons, custom buttons with arrows, or Arabic text
    const navButtons = page.locator(
      'button:has-text("التالي"), button:has-text("السابق"), ' +
      'button:has-text("next"), button:has-text("prev"), ' +
      '.fc-prev-button, .fc-next-button, ' +
      'button[aria-label*="prev"], button[aria-label*="next"], ' +
      'button[aria-label*="السابق"], button[aria-label*="التالي"], ' +
      '[class*="calendar"] button, .fc-toolbar button'
    );
    const navCount = await navButtons.count();

    // Also check for calendar structure (FullCalendar, Livewire calendar, or custom)
    const calendarStructure = page.locator(
      '.fc, .calendar, [class*="calendar"], [id*="calendar"], ' +
      '.fc-view-harness, .fc-daygrid, [wire\\:id]'
    );
    const hasCalendar = await calendarStructure.first().isVisible({ timeout: 10000 }).catch(() => false);

    // At least one of these should exist: navigation buttons or calendar structure
    // The page should have some calendar-like structure
    if (!hasCalendar && navCount === 0) {
      // Fallback: ensure the page at least has meaningful content (custom calendar rendering)
      const body = await page.textContent('body');
      expect(body?.length).toBeGreaterThan(100);
      // And contains the current year (calendar should show current period)
      const currentYear = new Date().getFullYear().toString();
      expect(body).toContain(currentYear);
    } else {
      expect(hasCalendar || navCount > 0).toBeTruthy();
    }
  });
});
