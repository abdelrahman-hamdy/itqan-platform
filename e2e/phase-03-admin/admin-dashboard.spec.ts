import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertRTL, waitForLivewire, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';

const ADMIN = 'https://itqanway.com/admin';

test.describe('Admin Dashboard', () => {
  test('dashboard loads successfully', async ({ adminPage: page }) => {
    await page.goto(ADMIN);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await assertNoPHPErrors(page);
  });

  test('dashboard has RTL layout', async ({ adminPage: page }) => {
    await page.goto(ADMIN);
    await page.waitForLoadState('networkidle');
    await assertRTL(page);
  });

  test('dashboard shows widgets', async ({ adminPage: page }) => {
    await page.goto(ADMIN);
    await page.waitForLoadState('networkidle');
    await waitForLivewire(page);
    await assertNotLoginPage(page);
    const widgets = page.locator('.fi-wi-stats-overview, .fi-widget');
    await expect(widgets.first()).toBeVisible({ timeout: 15000 });
  });

  test('sidebar navigation is visible', async ({ adminPage: page }) => {
    await page.goto(ADMIN);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    const sidebar = page.locator('aside.fi-sidebar, .fi-sidebar, nav.fi-sidebar-nav, [role="complementary"]').first();
    await expect(sidebar).toBeAttached({ timeout: 10000 });
  });

  test('sidebar has navigation groups', async ({ adminPage: page }) => {
    await page.goto(ADMIN);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    // Check for sidebar navigation items
    const navItems = page.locator('.fi-sidebar-group, .fi-sidebar-item, li.fi-sidebar-group, a.fi-sidebar-item-button, [role="complementary"] a');
    const count = await navItems.count();
    expect(count).toBeGreaterThan(3);
  });

  test('admin branding is visible', async ({ adminPage: page }) => {
    await page.goto(ADMIN);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    // The sidebar has "منصة إتقان للأعمال" and the topbar has "منصة مَعِين"
    const body = await page.textContent('body');
    expect(body).toMatch(/إتقان|مَعِين|itqan/i);
  });

  test('user menu is accessible', async ({ adminPage: page }) => {
    await page.goto(ADMIN);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    const userMenu = page.locator('.fi-user-menu, .fi-topbar button, button[class*="avatar"], .fi-avatar, [x-data] img[class*="rounded-full"], button:has-text("قائمة المستخدم")').first();
    const hasMenu = await userMenu.isVisible({ timeout: 5000 }).catch(() => false);
    expect(hasMenu).toBeTruthy();
  });
});
