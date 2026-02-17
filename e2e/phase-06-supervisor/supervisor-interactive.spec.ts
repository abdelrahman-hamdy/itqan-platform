import { expect } from '@playwright/test';
import { supervisorTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire, assertTableHasData, openTableFilters } from '../fixtures/filament.fixture';
import { assertMeaningfulContent } from '../fixtures/helpers';

const BASE = 'https://e2e-test.itqanway.com/supervisor-panel';

test.describe('Supervisor Interactive Flows', () => {
  test('dashboard to monitored circles', async ({ supervisorPage: page }) => {
    // Step 1: Visit supervisor dashboard
    await page.goto(BASE);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);
    await assertMeaningfulContent(page);

    // Step 2: Navigate to quran circles (supervised circles)
    await page.goto(`${BASE}/monitored-group-circles`);
    await page.waitForLoadState('networkidle');
    await assertNoServerError(page);

    // Step 3: Navigate to quran sessions
    await page.goto(`${BASE}/monitored-all-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNoServerError(page);
  });

  test('circles monitoring with filters', async ({ supervisorPage: page }) => {
    // Step 1: Visit monitored all sessions page
    await page.goto(`${BASE}/monitored-all-sessions`);
    await page.waitForLoadState('networkidle');
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Step 2: Verify filter panel exists by attempting to open it
    await waitForLivewire(page);
    await openTableFilters(page);
  });
});
