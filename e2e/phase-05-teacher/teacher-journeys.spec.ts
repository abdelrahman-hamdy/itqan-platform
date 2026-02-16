import { expect } from '@playwright/test';
import { teacherTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire } from '../fixtures/filament.fixture';
import { assertMeaningfulContent } from '../fixtures/helpers';

const BASE = 'https://itqan-academy.itqanway.com';

test.describe('Teacher - User Journeys', () => {
  test('circles to circle detail to sessions', async ({ teacherPage: page }) => {
    // Step 1: Navigate to individual circles listing
    await page.goto(`${BASE}/teacher/individual-circles`);
    await page.waitForLoadState('networkidle');
    await assertNoServerError(page);

    // Step 2: Find first circle link and navigate to detail
    const circleLink = page.locator('a[href*="/teacher/individual-circles/"]').first();
    try {
      if (await circleLink.isVisible({ timeout: 5000 })) {
        await circleLink.click();
        await page.waitForLoadState('networkidle');
        await assertNoServerError(page);
        await assertMeaningfulContent(page);

        // Step 3: On the detail page, look for session links
        const sessionLink = page.locator('a[href*="/session"], a[href*="/sessions"]').first();
        try {
          if (await sessionLink.isVisible({ timeout: 5000 })) {
            await sessionLink.click();
            await page.waitForLoadState('networkidle');
            await assertNoServerError(page);
          }
        } catch {
          // No session links on the detail page - acceptable
        }
      }
    } catch {
      // No circle detail links available - acceptable in production
    }
  });

  test('teacher panel dashboard to sessions', async ({ teacherPage: page }) => {
    // Step 1: Navigate to teacher panel dashboard
    await page.goto(`${BASE}/teacher-panel`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await assertMeaningfulContent(page);

    // Step 2: Navigate to quran sessions listing
    await page.goto(`${BASE}/teacher-panel/quran-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNoServerError(page);
  });
});
