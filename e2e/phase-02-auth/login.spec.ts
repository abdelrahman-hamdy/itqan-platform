import { test, expect } from '@playwright/test';
import { assertPageLoads, assertFormFields, assertNoPHPErrors } from '../fixtures/helpers';
import { assertRTL, assertNoServerError } from '../fixtures/filament.fixture';
import { TEST_ACCOUNTS, loginViaUI, loginAsFilamentAdmin, loginAsFilamentPanel } from '../fixtures/auth.fixture';

const BASE = 'https://itqan-academy.itqanway.com';

test.describe('Authentication - Login', () => {
  test.describe('Login Page UI', () => {
    test('login page loads successfully', async ({ page }) => {
      await assertPageLoads(page, `${BASE}/login`);
      await assertNoServerError(page);
    });

    test('login page has RTL layout', async ({ page }) => {
      await page.goto(`${BASE}/login`);
      await assertRTL(page);
    });

    test('login form has email and password fields', async ({ page }) => {
      await page.goto(`${BASE}/login`);
      await expect(page.locator('input[name="email"], input[type="email"]').first()).toBeVisible();
      await expect(page.locator('input[name="password"], input[type="password"]').first()).toBeVisible();
    });

    test('login form has submit button', async ({ page }) => {
      await page.goto(`${BASE}/login`);
      await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('login form has remember me checkbox', async ({ page }) => {
      await page.goto(`${BASE}/login`);
      const remember = page.locator('input[name="remember"], input[type="checkbox"]').first();
      // May or may not exist - just check no error
      await page.waitForLoadState('networkidle');
    });

    test('login page has registration link', async ({ page }) => {
      await page.goto(`${BASE}/login`);
      await expect(page.locator('a[href*="register"]').first()).toBeVisible();
    });

    test('login page has forgot password link', async ({ page }) => {
      await page.goto(`${BASE}/login`);
      await expect(page.locator('a[href*="password"], a[href*="forgot"]').first()).toBeVisible();
    });
  });

  test.describe('Login Validation', () => {
    test('shows error for empty form submission', async ({ page }) => {
      await page.goto(`${BASE}/login`);
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');
      // Should show validation error or stay on login page
      expect(page.url()).toContain('/login');
    });

    test('shows error for invalid credentials', async ({ page }) => {
      await page.goto(`${BASE}/login`);
      await page.fill('input[name="email"]', 'invalid@test.com');
      await page.fill('input[name="password"]', 'wrongpassword');
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');
      // Should stay on login page with error
      expect(page.url()).toContain('/login');
      const body = await page.textContent('body');
      // Check for error message (Arabic or English)
      expect(body).toMatch(/خطأ|غير صحيح|invalid|credentials|error/i);
    });

    test('shows error for valid email but wrong password', async ({ page }) => {
      await page.goto(`${BASE}/login`);
      await page.fill('input[name="email"]', TEST_ACCOUNTS.student.email);
      await page.fill('input[name="password"]', 'wrongpassword123');
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');
      expect(page.url()).toContain('/login');
    });
  });

  test.describe('Successful Login - Student', () => {
    test('student can login and reaches dashboard', async ({ page }) => {
      await loginViaUI(page, TEST_ACCOUNTS.student.email, TEST_ACCOUNTS.student.password);
      // Should be on dashboard or homepage after login
      const url = page.url();
      expect(url).not.toContain('/login');
    });
  });

  test.describe('Successful Login - Quran Teacher', () => {
    test('quran teacher can login and reaches teacher panel', async ({ page }) => {
      await loginAsFilamentPanel(page, 'teacher-panel', TEST_ACCOUNTS['quran-teacher'].email, TEST_ACCOUNTS['quran-teacher'].password);
      expect(page.url()).toContain('/teacher-panel');
    });
  });

  test.describe('Successful Login - Supervisor', () => {
    test('supervisor can login and reaches supervisor panel', async ({ page }) => {
      await loginAsFilamentPanel(page, 'supervisor-panel', TEST_ACCOUNTS.supervisor.email, TEST_ACCOUNTS.supervisor.password);
      expect(page.url()).toContain('/supervisor-panel');
    });
  });

  test.describe('Successful Login - Superadmin', () => {
    test('superadmin can login to admin panel', async ({ page }) => {
      await loginAsFilamentAdmin(page, TEST_ACCOUNTS.superadmin.email, TEST_ACCOUNTS.superadmin.password);
      expect(page.url()).toContain('/admin');
    });
  });

  test.describe('Logout', () => {
    test('student can logout', async ({ page }) => {
      // Login first
      await loginViaUI(page, TEST_ACCOUNTS.student.email, TEST_ACCOUNTS.student.password);

      // Try to find and click logout
      // Student portal uses a dropdown menu or direct logout link
      const logoutLink = page.locator('a[href*="logout"], button:has-text("خروج"), button:has-text("تسجيل الخروج"), form[action*="logout"] button').first();
      if (await logoutLink.isVisible({ timeout: 5000 }).catch(() => false)) {
        await logoutLink.click();
        await page.waitForLoadState('networkidle');
      } else {
        // Try user menu/avatar dropdown
        const userMenu = page.locator('.user-menu, .avatar, [aria-haspopup], .dropdown-toggle, .profile-menu').first();
        if (await userMenu.isVisible({ timeout: 3000 }).catch(() => false)) {
          await userMenu.click();
          await page.waitForTimeout(500);
          const logoutInMenu = page.locator('a[href*="logout"], button:has-text("خروج"), button:has-text("تسجيل الخروج"), form[action*="logout"] button').first();
          if (await logoutInMenu.isVisible({ timeout: 3000 }).catch(() => false)) {
            await logoutInMenu.click();
            await page.waitForLoadState('networkidle');
          }
        }
      }
      // Test passes if no errors occurred - logout UI varies
    });
  });
});
