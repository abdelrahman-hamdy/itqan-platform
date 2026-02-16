import { test, expect } from '@playwright/test';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';
import { assertRTL, assertNoServerError } from '../fixtures/filament.fixture';

const BASE = 'https://itqan-academy.itqanway.com';

test.describe('Academy Public Pages', () => {
  test.describe('Academy Homepage', () => {
    test('academy homepage loads', async ({ page }) => {
      const response = await page.goto(BASE);
      expect(response?.status()).toBeLessThan(400);
      await assertNoServerError(page);
    });

    test('academy homepage has branding', async ({ page }) => {
      await page.goto(BASE);
      // Check for academy branding - may be image, SVG, or text
      const branding = page.locator('img[src*="logo"], .logo, [alt*="إتقان"], [alt*="itqan"], svg, header img, a img').first();
      const hasBranding = await branding.isVisible({ timeout: 5000 }).catch(() => false);
      if (!hasBranding) {
        // Fallback: check for academy name in page text
        const body = await page.textContent('body');
        expect(body).toMatch(/إتقان|معين|itqan/i);
      }
    });

    test('academy homepage has RTL layout', async ({ page }) => {
      await page.goto(BASE);
      await assertRTL(page);
    });
  });

  test.describe('Legal Pages', () => {
    test('terms page loads', async ({ page }) => {
      await assertPageLoads(page, `${BASE}/terms`);
    });

    test('privacy policy page loads', async ({ page }) => {
      await assertPageLoads(page, `${BASE}/privacy-policy`);
    });

    test('about us page loads', async ({ page }) => {
      await assertPageLoads(page, `${BASE}/about-us`);
    });
  });

  test.describe('Teacher Listings', () => {
    test('quran teachers listing page loads', async ({ page }) => {
      await assertPageLoads(page, `${BASE}/quran-teachers`);
      await assertNoServerError(page);
    });

    test('quran teachers page shows teacher cards', async ({ page }) => {
      await page.goto(`${BASE}/quran-teachers`);
      await page.waitForLoadState('networkidle');
      // Should have at least some content (teacher cards or "no teachers" message)
      const body = await page.textContent('body');
      expect(body?.length).toBeGreaterThan(100);
    });

    test('academic teachers listing page loads', async ({ page }) => {
      await assertPageLoads(page, `${BASE}/academic-teachers`);
    });
  });

  test.describe('Circle & Course Listings', () => {
    test('quran circles listing page loads', async ({ page }) => {
      await assertPageLoads(page, `${BASE}/quran-circles`);
    });

    test('interactive courses listing page loads', async ({ page }) => {
      await assertPageLoads(page, `${BASE}/interactive-courses`);
    });

    test('recorded courses listing page loads', async ({ page }) => {
      await assertPageLoads(page, `${BASE}/courses`);
    });
  });

  test.describe('Auth Redirects', () => {
    test('student dashboard requires authentication or shows content', async ({ page }) => {
      const response = await page.goto(`${BASE}/student/dashboard`);
      await page.waitForLoadState('networkidle');
      // Page should not have a server error
      expect(response?.status()).not.toBe(500);
      expect(response?.status()).not.toBe(502);
    });

    test('teacher panel redirects to login when not authenticated', async ({ page }) => {
      await page.goto(`${BASE}/teacher-panel`);
      await page.waitForLoadState('networkidle');
      expect(page.url()).toContain('/login');
    });
  });
});
