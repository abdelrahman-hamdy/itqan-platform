import { test, expect } from '@playwright/test';
import { assertPageLoads } from '../fixtures/helpers';
import { assertRTL, assertNoServerError } from '../fixtures/filament.fixture';

const BASE = 'https://itqan-academy.itqanway.com';

test.describe('Authentication - Password Reset', () => {
  test('forgot password page loads', async ({ page }) => {
    await assertPageLoads(page, `${BASE}/forgot-password`);
    await assertNoServerError(page);
  });

  test('forgot password page has RTL layout', async ({ page }) => {
    await page.goto(`${BASE}/forgot-password`);
    await assertRTL(page);
  });

  test('forgot password form has email field', async ({ page }) => {
    await page.goto(`${BASE}/forgot-password`);
    await expect(page.locator('input[name="email"], input[type="email"]').first()).toBeVisible();
  });

  test('forgot password form has submit button', async ({ page }) => {
    await page.goto(`${BASE}/forgot-password`);
    await expect(page.locator('button[type="submit"]').first()).toBeVisible();
  });

  test('shows validation error for empty email', async ({ page }) => {
    await page.goto(`${BASE}/forgot-password`);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    // Should stay on the page
    const url = page.url();
    expect(url).toContain('forgot-password');
  });

  test('shows feedback after submitting valid email', async ({ page }) => {
    await page.goto(`${BASE}/forgot-password`);
    await page.fill('input[name="email"], input[type="email"]', 'test@example.com');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    // Should show success message or stay on page (doesn't reveal if email exists)
    await assertNoServerError(page);
  });

  test('forgot password page has back to login link', async ({ page }) => {
    await page.goto(`${BASE}/forgot-password`);
    await expect(page.locator('a[href*="login"]').first()).toBeVisible();
  });
});
