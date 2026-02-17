import { expect } from '@playwright/test';
import { parentTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage } from '../fixtures/filament.fixture';
import { assertNoPHPErrors } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com';

test.describe('Parent - Children Management', () => {
  test('children list page loads', async ({ parentPage: page }) => {
    await page.goto(`${BASE}/parent/children`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
  });
});
