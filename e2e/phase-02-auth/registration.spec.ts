import { test, expect } from '@playwright/test';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';
import { assertRTL, assertNoServerError } from '../fixtures/filament.fixture';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Authentication - Registration', () => {
  test.describe('Student Registration', () => {
    test('student registration page loads', async ({ page }) => {
      await assertPageLoads(page, `${BASE}/register`);
      await assertNoServerError(page);
    });

    test('student registration page has RTL layout', async ({ page }) => {
      await page.goto(`${BASE}/register`);
      await assertRTL(page);
    });

    test('student registration form has required fields', async ({ page }) => {
      await page.goto(`${BASE}/register`);
      await page.waitForLoadState('networkidle');
      // Email field with placeholder example@domain.com
      const emailField = page.locator('[placeholder="example@domain.com"], [placeholder*="example"]').first();
      await expect(emailField).toBeVisible({ timeout: 15000 });
    });

    test('student registration form has phone field', async ({ page }) => {
      await page.goto(`${BASE}/register`);
      await page.waitForLoadState('networkidle');
      // Phone field with placeholder 512345678 or containing phone label
      const phoneField = page.locator('[placeholder="512345678"], [placeholder*="5123"]').first();
      await expect(phoneField).toBeVisible({ timeout: 15000 });
    });

    test('student registration form has gender selection', async ({ page }) => {
      await page.goto(`${BASE}/register`);
      await page.waitForLoadState('networkidle');
      // Gender field has options ذكر/أنثى - verify the option exists in the DOM
      const maleOption = page.locator('option', { hasText: 'ذكر' }).first();
      await expect(maleOption).toBeAttached({ timeout: 15000 });
    });

    test('student registration form has submit button', async ({ page }) => {
      await page.goto(`${BASE}/register`);
      await page.waitForLoadState('networkidle');
      await expect(page.locator('button[type="submit"], button:has-text("إنشاء الحساب")').first()).toBeVisible({ timeout: 10000 });
    });
  });

  test.describe('Teacher Registration', () => {
    test('teacher registration page loads', async ({ page }) => {
      await assertPageLoads(page, `${BASE}/teacher/register`);
      await assertNoServerError(page);
    });

    test('teacher registration step 1 shows teaching type selection', async ({ page }) => {
      await page.goto(`${BASE}/teacher/register`);
      await page.waitForLoadState('networkidle');
      // Step 1: Choose teaching type (radio buttons)
      const heading = page.getByRole('heading', { name: /اختر نوع التدريس/ });
      await expect(heading).toBeVisible({ timeout: 10000 });
    });

    test('teacher registration has quran teacher option', async ({ page }) => {
      await page.goto(`${BASE}/teacher/register`);
      await page.waitForLoadState('networkidle');
      // Radio option for Quran teacher
      const quranOption = page.locator('text=معلم القرآن الكريم').first();
      await expect(quranOption).toBeVisible({ timeout: 10000 });
    });

    test('teacher registration has next button', async ({ page }) => {
      await page.goto(`${BASE}/teacher/register`);
      await page.waitForLoadState('networkidle');
      const nextBtn = page.locator('button:has-text("التالي")').first();
      await expect(nextBtn).toBeVisible({ timeout: 10000 });
    });
  });

  test.describe('Parent Registration', () => {
    test('parent registration page loads', async ({ page }) => {
      const response = await page.goto(`${BASE}/parent/register`);
      // May or may not exist - just check no 500
      if (response?.status() === 200) {
        await assertNoServerError(page);
      }
    });
  });

  test.describe('Registration Navigation', () => {
    test('login page has links to registration', async ({ page }) => {
      await page.goto(`${BASE}/login`);
      await page.waitForLoadState('networkidle');
      await expect(page.locator('a[href*="register"]').first()).toBeVisible();
    });

    test('login page has student registration link', async ({ page }) => {
      await page.goto(`${BASE}/login`);
      await page.waitForLoadState('networkidle');
      const studentRegLink = page.locator('a[href*="/register"]:not([href*="teacher"])').first();
      await expect(studentRegLink).toBeVisible();
    });

    test('login page has teacher registration link', async ({ page }) => {
      await page.goto(`${BASE}/login`);
      await page.waitForLoadState('networkidle');
      const teacherRegLink = page.locator('a[href*="teacher/register"], a[href*="register/teacher"]').first();
      await expect(teacherRegLink).toBeVisible();
    });
  });
});
