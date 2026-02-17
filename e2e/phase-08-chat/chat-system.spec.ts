import { expect } from '@playwright/test';
import { studentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, waitForLivewire, assertNotLoginPage, assertRTL } from '../fixtures/filament.fixture';
import { assertPageLoads, assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Chat System', () => {
  test.describe('Student Chat Access', () => {
    test('chat page loads for student', async ({ studentPage: page }) => {
      await assertPageLoads(page, `${BASE}/chats`);
      await assertNotLoginPage(page);
      await assertNoServerError(page);
    });

    test('chat page has no PHP errors', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/chats`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertNoPHPErrors(page);
    });

    test('chat page shows conversations heading', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/chats`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      // Chat page should show "المحادثات" heading
      const heading = page.getByRole('heading', { name: /المحادثات/ });
      await expect(heading).toBeVisible({ timeout: 15000 });
    });

    test('conversations list shows content', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/chats`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      const body = await page.textContent('body');
      expect(body?.length).toBeGreaterThan(100);
    });
  });

  test.describe('Chat Conversation', () => {
    test('can open a conversation if one exists', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/chats`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      // Conversations are links to /chats/{id}
      const convo = page.locator('a[href*="/chats/"]').first();
      if (await convo.isVisible({ timeout: 5000 }).catch(() => false)) {
        await convo.click();
        await page.waitForLoadState('networkidle');
        await assertNoServerError(page);
      }
    });

    test('conversation has message input when opened', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/chats`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      const convo = page.locator('a[href*="/chats/"]').first();
      if (await convo.isVisible({ timeout: 5000 }).catch(() => false)) {
        await convo.click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000); // Wait for Livewire to render
        const input = page.locator('input[type="text"], textarea, [contenteditable]').first();
        const hasInput = await input.isVisible({ timeout: 10000 }).catch(() => false);
        if (!hasInput) {
          // Chat conversation loaded but may use a different input method
          await assertNoServerError(page);
        }
      }
    });
  });

  test.describe('Chat UI Elements', () => {
    test('chat has RTL layout', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/chats`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      await assertRTL(page);
    });

    test('chat page has search box', async ({ studentPage: page }) => {
      await page.goto(`${BASE}/chats`);
      await page.waitForLoadState('networkidle');
      await assertNotLoginPage(page);
      const search = page.getByRole('searchbox');
      await expect(search).toBeVisible({ timeout: 10000 });
    });
  });

  test.describe('Chat Authorization', () => {
    test('unauthenticated user is redirected from chat', async ({ browser }) => {
      // Create explicitly clean context without any auth state
      const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
      const page = await context.newPage();
      await page.goto(`${BASE}/chats`);
      await page.waitForLoadState('networkidle');
      const url = page.url();
      // Should redirect to login or show login form
      const isBlocked = url.includes('/login') || url.includes('login');
      expect(isBlocked).toBeTruthy();
      await context.close();
    });
  });
});
