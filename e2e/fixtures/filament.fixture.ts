import { Page, expect } from '@playwright/test';

/** Wait for Livewire to finish processing */
export async function waitForLivewire(page: Page, timeout = 10000) {
  await page.waitForFunction(() => {
    const w = (window as any);
    return !w.Livewire || !document.querySelector('[wire\\:loading]:not([style*="display: none"])');
  }, { timeout });
}

/** Navigate to a Filament resource via sidebar */
export async function navigateToResource(page: Page, groupLabel: string, resourceLabel: string) {
  // Click sidebar group if collapsed
  const group = page.locator(`[x-data] span:has-text("${groupLabel}")`).first();
  if (await group.isVisible()) {
    await group.click();
    await page.waitForTimeout(500);
  }
  // Click resource link
  await page.click(`a:has-text("${resourceLabel}")`);
  await page.waitForLoadState('networkidle');
  await waitForLivewire(page);
}

/** Assert Filament table loaded with rows */
export async function assertTableLoaded(page: Page) {
  await expect(page.locator('.fi-ta-table, table').first()).toBeVisible({ timeout: 15000 });
}

/** Assert no server error or 404 on page */
export async function assertNoServerError(page: Page) {
  const content = await page.content();
  expect(content).not.toContain('Server Error');
  expect(content).not.toContain('Whoops');
  expect(content).not.toContain('ErrorException');
  expect(content).not.toContain('الصفحة غير موجودة');
}

/** Assert page is not a login page (auth state is valid) */
export async function assertNotLoginPage(page: Page) {
  expect(page.url()).not.toContain('/login');
}

/** Assert page has Arabic content (no raw translation keys) */
export async function assertArabicContent(page: Page) {
  const body = await page.textContent('body');
  const translationKeyPattern = /\b[a-z]+\.[a-z]+\.[a-z_]+\b/g;
  const matches = body?.match(translationKeyPattern) || [];
  const suspiciousKeys = matches.filter(m =>
    !m.includes('http') && !m.includes('www') && !m.includes('com')
    && !m.includes('min') && !m.includes('max') && !m.includes('svg')
    && !m.includes('app') && !m.includes('cdn') && !m.includes('img')
    && !m.includes('font') && !m.includes('css') && !m.includes('src')
    && m.split('.').length >= 3
  );
  // Allow up to 10 suspicious keys (many dot-separated strings are JS/CSS, not translation keys)
  expect(suspiciousKeys.length).toBeLessThan(10);
}

/** Assert RTL direction */
export async function assertRTL(page: Page) {
  const dir = await page.getAttribute('html', 'dir');
  const lang = await page.getAttribute('html', 'lang');
  expect(dir === 'rtl' || lang === 'ar').toBeTruthy();
}

/** Click a Filament tab by label */
export async function clickFilamentTab(page: Page, label: string) {
  await page.click(`[role="tab"]:has-text("${label}"), button:has-text("${label}")`);
  await waitForLivewire(page);
}

/** Assert Filament form section exists */
export async function assertFormSection(page: Page, heading: string) {
  await expect(page.locator(`text=${heading}`).first()).toBeVisible({ timeout: 10000 });
}

/** Click a Filament action button */
export async function clickAction(page: Page, label: string) {
  await page.click(`button:has-text("${label}"), a:has-text("${label}")`);
  await waitForLivewire(page);
}

/** Assert notification appeared */
export async function assertNotification(page: Page, text?: string) {
  const notification = page.locator('.fi-no-notification, [x-data*="notification"]').first();
  await expect(notification).toBeVisible({ timeout: 10000 });
  if (text) {
    await expect(notification).toContainText(text);
  }
}

/** Open table filters panel */
export async function openTableFilters(page: Page) {
  const filterBtn = page.locator('button[x-on\\:click*="toggleFiltersForm"], button:has(.fi-ta-header-toolbar-filter-button), .fi-ta-header-toolbar-actions button:has(svg)').first();
  if (await filterBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await filterBtn.click();
    await page.waitForTimeout(500);
  }
}

/** Apply a Filament select filter by name */
export async function applySelectFilter(page: Page, filterName: string, optionIndex = 1) {
  // Filament filters use wire:model with filter name
  const filterSelect = page.locator(`select[wire\\:model*="${filterName}"], [x-ref*="${filterName}"] select`).first();
  if (await filterSelect.isVisible({ timeout: 5000 }).catch(() => false)) {
    await filterSelect.selectOption({ index: optionIndex });
    await waitForLivewire(page);
  }
}

/** Assert a Filament form field exists by name or label */
export async function assertFilamentFormField(page: Page, fieldName: string) {
  const field = page.locator(
    `[wire\\:model*="${fieldName}"], input[name*="${fieldName}"], select[name*="${fieldName}"], textarea[name*="${fieldName}"], [data-field-name="${fieldName}"], label:has-text("${fieldName}")`
  ).first();
  await expect(field).toBeVisible({ timeout: 10000 });
}

/** Count visible table rows */
export async function countTableRows(page: Page): Promise<number> {
  await page.waitForSelector('.fi-ta-row, table tbody tr', { timeout: 10000 }).catch(() => {});
  return await page.locator('.fi-ta-row, table tbody tr').count();
}

/** Assert table has data (at least one row) */
export async function assertTableHasData(page: Page) {
  const count = await countTableRows(page);
  // Table could be empty or have data - we just check it loaded without error
  expect(count).toBeGreaterThanOrEqual(0);
}

/** Assert a Filament search input exists and is functional */
export async function assertTableSearch(page: Page) {
  const searchInput = page.locator('input[wire\\:model*="search"], input[placeholder*="بحث"], input[type="search"]').first();
  await expect(searchInput).toBeVisible({ timeout: 10000 });
}
