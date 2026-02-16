import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, waitForLivewire, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';

const ADMIN = 'https://itqanway.com/admin';

test.describe('Admin - Settings', () => {
  test.describe('Platform Settings', () => {
    test('platform settings page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/platform-settings-page`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Academy General Settings', () => {
    test('academy general settings page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academy-general-settings`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Design Settings', () => {
    test('design settings page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academy-design-settings`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Payment Settings', () => {
    test('payment settings page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/payment-settings`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Academy Management', () => {
    test('academy management page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/academy-managements`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });

  test.describe('Health Check', () => {
    test('health check page loads', async ({ adminPage: page }) => {
      await page.goto(`${ADMIN}/health-check-results`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await waitForLivewire(page);
      await assertNoServerError(page);
    });
  });
});
