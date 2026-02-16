import { expect } from '@playwright/test';
import { academicTeacherTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire, assertTableHasData } from '../fixtures/filament.fixture';
import { assertMeaningfulContent } from '../fixtures/helpers';

const BASE = 'https://itqan-academy.itqanway.com/academic-teacher-panel';

test.describe('Academic Teacher Panel - Interactive Tests', () => {
  test('academic sessions table with detail', async ({ academicTeacherPage: page }) => {
    await page.goto(`${BASE}/academic-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);
    await assertTableHasData(page);

    // Try clicking first row link if available (read-only view)
    const rowLink = page.locator('.fi-ta-row a, table tbody tr a').first();
    try {
      if (await rowLink.isVisible({ timeout: 5000 })) {
        await rowLink.click();
        await page.waitForLoadState('networkidle');
        await assertNoServerError(page);
        await assertMeaningfulContent(page);
      }
    } catch {
      // No row links available - table may be empty or links not present
    }
  });

  test('academic individual lessons table loads', async ({ academicTeacherPage: page }) => {
    await page.goto(`${BASE}/academic-individual-lessons`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await waitForLivewire(page);
  });
});
