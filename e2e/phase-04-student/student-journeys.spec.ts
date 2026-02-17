import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire } from '../fixtures/filament.fixture';
import { assertMeaningfulContent, assertNoPHPErrors, assertHasContentSections, assertHasNavigation, assertContainsArabic } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Student - User Journeys', () => {
  test('homepage to teachers to detail', async ({ studentPage: page }) => {
    // Step 1: Visit homepage
    await page.goto(`${BASE}/`);
    await page.waitForLoadState('networkidle');
    await assertNoServerError(page);

    // Step 2: Navigate to quran teachers
    await page.goto(`${BASE}/quran-teachers`);
    await page.waitForLoadState('networkidle');
    await assertNoServerError(page);
    await assertMeaningfulContent(page);

    // Step 3: Click first teacher card/link to view detail
    const teacherLink = page.locator('a[href*="/quran-teachers/"]').first();
    if (await teacherLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await teacherLink.click();
      await page.waitForLoadState('networkidle');
      await assertNoServerError(page);
      await assertNoPHPErrors(page);
    }
  });

  test('profile to subscriptions to detail', async ({ studentPage: page }) => {
    // Step 1: Visit student profile (authenticated starting point)
    await page.goto(`${BASE}/profile`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Step 2: Navigate to subscriptions
    await page.goto(`${BASE}/subscriptions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Step 3: If any subscription link found, click to view detail
    const subLink = page.locator('a[href*="/subscriptions/"], a[href*="/academic-subscriptions/"]').first();
    if (await subLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await subLink.click();
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      await assertNoPHPErrors(page);
    }
  });

  test('browse interactive courses to detail', async ({ studentPage: page }) => {
    // Step 1: Visit interactive courses listing
    await page.goto(`${BASE}/interactive-courses`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Step 2: Find and click first course link/card
    const courseLink = page.locator('a[href*="/interactive-courses/"]').first();
    if (await courseLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await courseLink.click();
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      await assertMeaningfulContent(page);
    }
  });
});
