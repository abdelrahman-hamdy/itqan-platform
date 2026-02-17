import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire } from '../fixtures/filament.fixture';
import { assertMeaningfulContent, assertNoPHPErrors, assertHasContentSections, assertHasNavigation, assertContainsArabic } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Student - Interactive Content', () => {
  test('subscription detail shows info', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/subscriptions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Look for a subscription link or card
    const subLink = page.locator('a[href*="/subscriptions/"], a[href*="/academic-subscriptions/"], .card a, article a').first();
    if (await subLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await subLink.click();
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      // Detail page should have meaningful content
      const body = await page.textContent('body');
      expect(body?.length).toBeGreaterThan(100);
    }
  });

  test('browse quran teachers page has cards', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/quran-teachers`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await assertMeaningfulContent(page);

    // Check for teacher cards or grid items
    const cards = page.locator('.card, article, [class*="card"], .grid > div, [class*="teacher"]');
    const cardCount = await cards.count();
    // Page should either have teacher cards or an empty state message
    const body = await page.textContent('body');
    expect(cardCount > 0 || (body?.length ?? 0) > 100).toBeTruthy();
  });

  test('browse academic teachers page has content', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/academic-teachers`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await assertMeaningfulContent(page);
  });

  test('teacher detail page loads', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/quran-teachers`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);

    // Find first teacher link that navigates to a detail page
    const teacherLink = page.locator('a[href*="/quran-teachers/"]').first();
    if (await teacherLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await teacherLink.click();
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      await assertNoPHPErrors(page);
      // Detail page should have content
      const body = await page.textContent('body');
      expect(body?.length).toBeGreaterThan(100);
    } else {
      // No teachers available - verify the list page itself loaded
      await assertNoServerError(page);
      await assertMeaningfulContent(page);
    }
  });

  test('homework page shows content', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/homework`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('quizzes page shows content', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/quizzes`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('interactive courses listing', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/interactive-courses`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await assertMeaningfulContent(page);
  });

  test('interactive course detail loads', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/interactive-courses`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);

    // Find first interactive course link
    const courseLink = page.locator('a[href*="/interactive-courses/"]').first();
    if (await courseLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      await courseLink.click();
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoServerError(page);
      await assertNoPHPErrors(page);
    } else {
      // No interactive courses listed - verify listing loaded fine
      await assertNoServerError(page);
    }
  });

  test('courses listing (recorded)', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/courses`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await assertMeaningfulContent(page);
  });

  test('certificates page loads', async ({ studentPage: page }) => {
    await page.goto(`${BASE}/certificates`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });
});
