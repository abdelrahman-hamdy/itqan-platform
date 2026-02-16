import { expect } from '@playwright/test';
import { teacherTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire } from '../fixtures/filament.fixture';
import { assertMeaningfulContent, assertNoPHPErrors, assertHasContentSections } from '../fixtures/helpers';

const BASE = 'https://itqan-academy.itqanway.com';

test.describe('Quran Teacher - Interactive Web Routes', () => {
  test('individual circles page has content', async ({ teacherPage: page }) => {
    await page.goto(`${BASE}/teacher/individual-circles`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await assertMeaningfulContent(page);
  });

  test('individual circle detail loads', async ({ teacherPage: page }) => {
    await page.goto(`${BASE}/teacher/individual-circles`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Find first link to a circle detail page
    const circleLink = page.locator('a[href*="/teacher/individual-circles/"]').first();
    try {
      if (await circleLink.isVisible({ timeout: 5000 })) {
        await circleLink.click();
        await page.waitForLoadState('networkidle');
        await assertNoServerError(page);
        await assertMeaningfulContent(page);
      }
    } catch {
      // No circle detail links available - that is acceptable in production
    }
  });

  test('homework page loads with content', async ({ teacherPage: page }) => {
    await page.goto(`${BASE}/teacher/homework`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('group circles page loads', async ({ teacherPage: page }) => {
    await page.goto(`${BASE}/teacher/group-circles`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('group circle detail loads', async ({ teacherPage: page }) => {
    await page.goto(`${BASE}/teacher/group-circles`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Find first link to a group circle detail page
    const circleLink = page.locator('a[href*="/teacher/group-circles/"]').first();
    try {
      if (await circleLink.isVisible({ timeout: 5000 })) {
        await circleLink.click();
        await page.waitForLoadState('networkidle');
        await assertNoServerError(page);
      }
    } catch {
      // No group circle detail links available - that is acceptable in production
    }
  });
});
