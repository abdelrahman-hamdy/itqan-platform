import { expect } from '@playwright/test';
import { parentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://itqan-academy.itqanway.com';

test.describe('Parent - Views', () => {
  test('parent upcoming sessions loads', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent/sessions/upcoming`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('parent session history loads', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent/sessions/history`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('parent subscriptions view loads', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent/subscriptions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('parent payments view loads', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent/payments`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('parent homework view loads', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent/homework`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('parent quizzes view loads', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent/quizzes`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('parent certificates view loads', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent/certificates`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('parent calendar view loads', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent/calendar`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });

  test('parent progress report loads', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent/reports/progress`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });
});
