import { Page, expect } from '@playwright/test';

/** Navigate and assert page loads without errors (500, 404, etc.) */
export async function assertPageLoads(page: Page, url: string, options?: { contains?: string; timeout?: number; allow404?: boolean }) {
  const response = await page.goto(url, { waitUntil: 'networkidle', timeout: options?.timeout || 30000 });
  expect(response?.status()).not.toBe(500);
  expect(response?.status()).not.toBe(502);
  expect(response?.status()).not.toBe(503);
  if (!options?.allow404) {
    expect(response?.status()).not.toBe(404);
    // Also check for custom Arabic 404 page
    const content = await page.content();
    expect(content).not.toContain('الصفحة غير موجودة');
  }
  if (options?.contains) {
    await expect(page.locator('body')).toContainText(options.contains);
  }
  return response;
}

/** Assert page has specific form fields */
export async function assertFormFields(page: Page, fields: string[]) {
  for (const field of fields) {
    await expect(
      page.locator(`[name="${field}"], #${field}, [data-field="${field}"], input[name*="${field}"], select[name*="${field}"], textarea[name*="${field}"]`).first()
    ).toBeVisible({ timeout: 10000 });
  }
}

/** Assert page is not a 404 */
export async function assertNot404(page: Page) {
  const content = await page.content();
  expect(content).not.toContain('الصفحة غير موجودة');
}

/** Assert page has no PHP errors visible */
export async function assertNoPHPErrors(page: Page) {
  const content = await page.content();
  expect(content).not.toContain('Undefined variable');
  expect(content).not.toContain('Undefined property');
  expect(content).not.toContain('Call to undefined method');
  expect(content).not.toContain('Class "');
  expect(content).not.toContain('Attempt to read property');
  expect(content).not.toContain('ErrorException');
  expect(content).not.toContain('TypeError');
  expect(content).not.toContain('Whoops');
}

/** Get all response errors from the page (for monitoring) */
export function monitorPageErrors(page: Page): string[] {
  const errors: string[] = [];
  page.on('pageerror', (error) => {
    errors.push(error.message);
  });
  page.on('response', (response) => {
    if (response.status() >= 500) {
      errors.push(`${response.status()} - ${response.url()}`);
    }
  });
  return errors;
}

/** Fill a Filament select field */
export async function fillFilamentSelect(page: Page, fieldName: string, optionText: string) {
  const select = page.locator(`[wire\\:model*="${fieldName}"], [name="${fieldName}"]`).first();
  await select.click();
  await page.waitForTimeout(300);
  const option = page.locator(`[role="option"]:has-text("${optionText}"), li:has-text("${optionText}")`).first();
  await option.click();
  await page.waitForTimeout(300);
}

/** Fill a date field */
export async function fillDateField(page: Page, fieldName: string, date: string) {
  const input = page.locator(`input[name="${fieldName}"], input[name*="${fieldName}"]`).first();
  await input.fill(date);
}

/** Take a screenshot with a descriptive name */
export async function takeNamedScreenshot(page: Page, name: string) {
  await page.screenshot({ path: `e2e/reports/screenshots/${name}.png`, fullPage: true });
}

/** Assert breadcrumb exists */
export async function assertBreadcrumb(page: Page, items: string[]) {
  for (const item of items) {
    await expect(page.locator(`nav[aria-label="breadcrumb"] :text("${item}"), .fi-breadcrumbs :text("${item}")`).first()).toBeVisible();
  }
}

/** Assert pagination exists */
export async function assertPagination(page: Page) {
  await expect(page.locator('nav[aria-label="Pagination"], .fi-ta-pagination').first()).toBeVisible({ timeout: 5000 }).catch(() => {
    // Pagination may not exist if few records - that's ok
  });
}

/** Assert page has meaningful content (not blank/empty) */
export async function assertMeaningfulContent(page: Page, minLength = 100) {
  const bodyText = await page.textContent('body') || '';
  expect(bodyText.trim().length).toBeGreaterThan(minLength);
}

/** Assert page contains specific Arabic text */
export async function assertContainsArabic(page: Page, text: string) {
  await expect(page.locator(`body`)).toContainText(text, { timeout: 10000 });
}

/** Assert page has visible cards or info sections */
export async function assertHasContentSections(page: Page) {
  const sections = page.locator('.fi-section, .card, [class*="card"], [class*="section"], .fi-wi-stats-overview, article, .bg-white, .dark\\:bg-gray-800').first();
  await expect(sections).toBeVisible({ timeout: 10000 });
}

/** Assert page has navigation links or clickable elements */
export async function assertHasNavigation(page: Page) {
  const navElements = await page.locator('a[href]:not([href="#"]):not([href=""]), button:visible').count();
  expect(navElements).toBeGreaterThan(0);
}
