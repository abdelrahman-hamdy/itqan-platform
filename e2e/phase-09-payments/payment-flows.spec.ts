import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertPageLoads } from '../fixtures/helpers';

const BASE = 'https://itqan-academy.itqanway.com';

test.describe('Payment Flows', () => {
  test.describe('Subscription Payment', () => {
    test('subscription payment page loads', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/subscriptions`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      const payBtn = page.locator('a[href*="pay"], a[href*="subscribe"], button:has-text("اشتراك"), button:has-text("دفع")').first();
      if (await payBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
        await payBtn.click();
        await page.waitForLoadState('networkidle');
        await assertNoServerError(page);
      }
    });
  });

  test.describe('Course Checkout', () => {
    test('course checkout page loads', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/courses`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      const courseLink = page.locator('a[href*="/courses/"]').first();
      if (await courseLink.isVisible({ timeout: 5000 }).catch(() => false)) {
        await courseLink.click();
        await page.waitForLoadState('networkidle');
        const enrollBtn = page.locator('button:has-text("اشتراك"), button:has-text("شراء"), a:has-text("اشتراك"), a:has-text("الالتحاق")').first();
        if (await enrollBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
          await enrollBtn.click();
          await page.waitForLoadState('networkidle');
          await assertNoServerError(page);
        }
      }
    });
  });

  test.describe('Payment Result Pages', () => {
    test('payment success page does not crash', async ({ studentPage: page }) => {
      const response = await page.goto(`${BASE}/payment/success`);
      // Payment success without a real transaction may redirect or 404 - just check no 500
      expect(response?.status()).not.toBe(500);
      expect(response?.status()).not.toBe(502);
    });

    test('payment failed page does not crash', async ({ studentPage: page }) => {
      const response = await page.goto(`${BASE}/payment/failed`);
      // Payment failed without a real transaction may redirect or 404 - just check no 500
      expect(response?.status()).not.toBe(500);
      expect(response?.status()).not.toBe(502);
    });

    test('payment callback handles missing parameters gracefully', async ({ studentPage: page }) => {
      const response = await page.goto(`${BASE}/payment/callback`);
      expect(response?.status()).not.toBe(500);
    });
  });
});
