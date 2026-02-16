import { expect } from '@playwright/test';
import { parentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire } from '../fixtures/filament.fixture';
import { assertMeaningfulContent, assertHasContentSections, assertHasNavigation } from '../fixtures/helpers';

const BASE = 'https://itqan-academy.itqanway.com';

test.describe('Parent Journey Flows', () => {
  test('dashboard to children to sessions', async ({ parentPage: page }) => {
    // Step 1: Visit parent dashboard
    await page.goto(`${BASE}/parent`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await assertMeaningfulContent(page);

    // Step 2: Navigate to children list
    await page.goto(`${BASE}/parent/children`);
    await page.waitForLoadState('networkidle');
    await assertNoServerError(page);

    // Step 3: Navigate to upcoming sessions
    await page.goto(`${BASE}/parent/sessions/upcoming`);
    await page.waitForLoadState('networkidle');
    await assertNoServerError(page);
  });

  test('dashboard to subscriptions to payments', async ({ parentPage: page }) => {
    // Step 1: Visit parent dashboard
    await page.goto(`${BASE}/parent`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Step 2: Navigate to subscriptions
    await page.goto(`${BASE}/parent/subscriptions`);
    await page.waitForLoadState('networkidle');
    await assertNoServerError(page);

    // Step 3: Navigate to payments
    await page.goto(`${BASE}/parent/payments`);
    await page.waitForLoadState('networkidle');
    await assertNoServerError(page);
  });

  test('homework to quizzes flow', async ({ parentPage: page }) => {
    // Step 1: Visit homework page
    await page.goto(`${BASE}/parent/homework`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Step 2: Navigate to quizzes
    await page.goto(`${BASE}/parent/quizzes`);
    await page.waitForLoadState('networkidle');
    await assertNoServerError(page);
  });
});
