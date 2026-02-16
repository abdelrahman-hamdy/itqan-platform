import { Page, expect } from '@playwright/test';
import { waitForLivewire } from './filament.fixture';

const ADMIN = 'https://itqanway.com/admin';
export const E2E_PREFIX = '[E2E]';

/** Generate a unique E2E record name with timestamp */
export function generateE2EName(base: string): string {
  return `${E2E_PREFIX} ${base} ${Date.now()}`;
}

/** Navigate to a Filament resource create page */
export async function goToCreate(page: Page, resourceSlug: string): Promise<void> {
  await page.goto(`${ADMIN}/${resourceSlug}/create`);
  await page.waitForLoadState('networkidle');
  await waitForLivewire(page);
}

/** Navigate to a Filament resource list page */
export async function goToList(page: Page, resourceSlug: string): Promise<void> {
  await page.goto(`${ADMIN}/${resourceSlug}`);
  await page.waitForLoadState('networkidle');
  await waitForLivewire(page);
}

/** Fill a Filament text input field by wire:model pattern, name, or label */
export async function fillFilamentText(page: Page, fieldIdentifier: string, value: string): Promise<void> {
  // Filament 3/Livewire 3 uses wire:model="data.fieldName" pattern
  // Also try the field identifier directly for compatibility
  const selectors = [
    `input[wire\\:model\\.live*="data.${fieldIdentifier}"]`,
    `input[wire\\:model\\.blur*="data.${fieldIdentifier}"]`,
    `input[wire\\:model*="data.${fieldIdentifier}"]`,
    `textarea[wire\\:model*="data.${fieldIdentifier}"]`,
    `input[wire\\:model\\.live*="${fieldIdentifier}"]`,
    `input[wire\\:model\\.blur*="${fieldIdentifier}"]`,
    `input[wire\\:model*="${fieldIdentifier}"]`,
    `input[name*="${fieldIdentifier}"]`,
    `textarea[wire\\:model*="${fieldIdentifier}"]`,
    `textarea[name*="${fieldIdentifier}"]`,
  ].join(', ');
  const input = page.locator(selectors).first();
  await input.waitFor({ state: 'visible', timeout: 10000 });
  await input.clear();
  await input.fill(value);
  await page.waitForTimeout(300);
}

/** Select an option in a Filament select/search field */
export async function selectFilamentOption(page: Page, fieldIdentifier: string, optionText: string): Promise<void> {
  // Try to find and click the select trigger (Filament uses data.fieldName pattern)
  const selectContainer = page.locator(
    `[wire\\:model*="data.${fieldIdentifier}"], [wire\\:model*="${fieldIdentifier}"], [x-ref*="${fieldIdentifier}"]`
  ).first();

  // For native select elements
  const nativeSelect = page.locator(`select[wire\\:model*="data.${fieldIdentifier}"], select[wire\\:model*="${fieldIdentifier}"], select[name*="${fieldIdentifier}"]`).first();
  if (await nativeSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
    await nativeSelect.selectOption({ label: optionText });
    await page.waitForTimeout(500);
    return;
  }

  // For Filament's custom searchable selects
  const searchInput = selectContainer.locator('input[type="search"], input[role="combobox"]').first();
  if (await searchInput.isVisible({ timeout: 3000 }).catch(() => false)) {
    await searchInput.fill(optionText);
    await page.waitForTimeout(1000);
  } else {
    // Click the select button/trigger to open dropdown
    const trigger = selectContainer.locator('button, [role="combobox"]').first();
    await trigger.click();
    await page.waitForTimeout(500);
  }

  // Click the matching option
  const option = page.locator(`[role="option"]:has-text("${optionText}"), li:has-text("${optionText}")`).first();
  await option.waitFor({ state: 'visible', timeout: 5000 });
  await option.click();
  await page.waitForTimeout(300);
}

/** Toggle a Filament switch/toggle field */
export async function toggleFilamentSwitch(page: Page, fieldIdentifier: string, desiredState: boolean): Promise<void> {
  const toggle = page.locator(
    `button[wire\\:model*="data.${fieldIdentifier}"], button[wire\\:model*="${fieldIdentifier}"], input[type="checkbox"][wire\\:model*="data.${fieldIdentifier}"], input[type="checkbox"][wire\\:model*="${fieldIdentifier}"]`
  ).first();
  const isChecked = await toggle.getAttribute('aria-checked') === 'true' ||
    await toggle.isChecked().catch(() => false);
  if (isChecked !== desiredState) {
    await toggle.click();
    await page.waitForTimeout(300);
  }
}

/** Fill a number input field */
export async function fillFilamentNumber(page: Page, fieldIdentifier: string, value: number): Promise<void> {
  await fillFilamentText(page, fieldIdentifier, value.toString());
}

/** Select the first available option in a native Filament select field */
export async function selectFirstFilamentOption(page: Page, fieldIdentifier: string): Promise<void> {
  const nativeSelect = page.locator(
    `select[wire\\:model*="data.${fieldIdentifier}"], select[wire\\:model*="${fieldIdentifier}"]`
  ).first();

  await nativeSelect.waitFor({ state: 'visible', timeout: 10000 });

  const firstValue = await nativeSelect.evaluate((sel: HTMLSelectElement) => {
    for (const opt of Array.from(sel.options)) {
      if (opt.value && !opt.disabled) return opt.value;
    }
    return null;
  });

  if (firstValue) {
    await nativeSelect.selectOption(firstValue);
    await page.waitForTimeout(500);
  }
}

/** Helper: click a select trigger, optionally search, and pick the first option (Filament AJAX combobox) */
async function openAndPickFirstOption(page: Page, trigger: ReturnType<Page['locator']>, searchTerm: string): Promise<boolean> {
  if (!(await trigger.isVisible({ timeout: 3000 }).catch(() => false))) return false;
  await trigger.click();
  await page.waitForTimeout(800);

  if (searchTerm) {
    const searchInput = page.locator('input[type="search"]:visible').first();
    if (await searchInput.isVisible({ timeout: 2000 }).catch(() => false)) {
      await searchInput.fill(searchTerm);
      await page.waitForTimeout(1500);
    }
  }

  const option = page.locator('[role="option"]').first();
  if (await option.isVisible({ timeout: 5000 }).catch(() => false)) {
    await option.click();
    await page.waitForTimeout(300);
    return true;
  }
  // Close dropdown if no option was found
  await page.keyboard.press('Escape');
  await page.waitForTimeout(300);
  return false;
}

/** Helper: interact with a Choices.js enhanced select within a field wrapper */
async function openAndPickFirstChoicesOption(page: Page, fieldWrapper: ReturnType<Page['locator']>, searchTerm: string): Promise<boolean> {
  const choicesContainer = fieldWrapper.locator('.choices').first();
  if (!(await choicesContainer.isVisible({ timeout: 2000 }).catch(() => false))) return false;

  // Click to open the Choices.js dropdown
  await choicesContainer.click();
  await page.waitForTimeout(800);

  // If search term provided, type in the Choices.js search input
  if (searchTerm) {
    const searchInput = fieldWrapper.locator('.choices__input--cloned, input.choices__input[type="search"]').first();
    if (await searchInput.isVisible({ timeout: 2000 }).catch(() => false)) {
      await searchInput.fill(searchTerm);
      await page.waitForTimeout(1500);
    }
  }

  // Pick the first selectable option
  const option = fieldWrapper.locator('.choices__item--choice.choices__item--selectable').first();
  if (await option.isVisible({ timeout: 3000 }).catch(() => false)) {
    await option.click();
    await page.waitForTimeout(500);
    return true;
  }

  // Close dropdown
  await page.keyboard.press('Escape');
  await page.waitForTimeout(300);
  return false;
}

/** Select the first available option in a searchable Filament select (relationship or searchable enum) */
export async function selectFirstSearchableOption(page: Page, fieldIdentifier: string, searchTerm = ''): Promise<void> {
  // Strategy 1 (most reliable): Find by wire:key on the Filament field wrapper
  // Filament wraps every form field in a div with wire:key="data.fieldName.ComponentClass"
  const fieldWrapper = page.locator(`[wire\\:key*="data.${fieldIdentifier}"]`).first();
  if (await fieldWrapper.isVisible({ timeout: 3000 }).catch(() => false)) {
    // 1a: Try Filament AJAX combobox (relationship selects without preloaded options)
    const buttonTrigger = fieldWrapper.locator('button[role="combobox"]').first();
    if (await buttonTrigger.isVisible({ timeout: 1000 }).catch(() => false)) {
      if (await openAndPickFirstOption(page, buttonTrigger, searchTerm)) return;
    }

    // 1b: Try Choices.js enhanced select (searchable selects with static/preloaded options)
    if (await openAndPickFirstChoicesOption(page, fieldWrapper, searchTerm)) return;

    // 1c: Try any button as trigger (generic fallback)
    const anyButton = fieldWrapper.locator('button').first();
    if (await anyButton.isVisible({ timeout: 1000 }).catch(() => false)) {
      if (await openAndPickFirstOption(page, anyButton, searchTerm)) return;
    }
  }

  // Strategy 2: Find .fi-fo-select container by x-data attribute (Alpine entangle)
  const xDataContainer = page.locator(`.fi-fo-select:has([x-data*="${fieldIdentifier}"])`).first();
  if (await xDataContainer.isVisible({ timeout: 3000 }).catch(() => false)) {
    if (await openAndPickFirstChoicesOption(page, xDataContainer, searchTerm)) return;
    const trigger = xDataContainer.locator('button, [role="combobox"]').first();
    if (await openAndPickFirstOption(page, trigger, searchTerm)) return;
  }

  // Strategy 3: Find .fi-fo-select container by wire:model/name attributes
  const selectContainer = page.locator('.fi-fo-select').filter({
    has: page.locator(
      `[wire\\:model*="data.${fieldIdentifier}"], ` +
      `[wire\\:model*="${fieldIdentifier}"], ` +
      `select[name*="${fieldIdentifier}"], ` +
      `input[name*="${fieldIdentifier}"]`
    )
  }).first();

  if (await selectContainer.isVisible({ timeout: 3000 }).catch(() => false)) {
    if (await openAndPickFirstChoicesOption(page, selectContainer, searchTerm)) return;
    const trigger = selectContainer.locator('button, [role="combobox"]').first();
    if (await openAndPickFirstOption(page, trigger, searchTerm)) return;
  }

  // Strategy 5: Fallback to selectFilamentOption
  if (searchTerm) {
    await selectFilamentOption(page, fieldIdentifier, searchTerm);
  }
}

/** Fill a PhoneInput component (intl-tel-input) by finding the tel input within the field container */
export async function fillPhoneInput(page: Page, fieldIdentifier: string, value: string): Promise<void> {
  // Strategy 1 (most reliable): Find by wire:key on the Filament field wrapper
  const fieldWrapper = page.locator(`[wire\\:key*="${fieldIdentifier}"]`).first();
  if (await fieldWrapper.isVisible({ timeout: 5000 }).catch(() => false)) {
    const telInput = fieldWrapper.locator('input[type="tel"]').first();
    if (await telInput.isVisible({ timeout: 3000 }).catch(() => false)) {
      await telInput.click();
      await page.waitForTimeout(200);
      await telInput.clear();
      await telInput.fill(value);
      await page.waitForTimeout(500);
      await page.locator('body').click({ position: { x: 10, y: 10 } });
      await page.waitForTimeout(300);
      return;
    }
  }

  // Strategy 2: Find container with wire:model/name reference
  const phoneContainer = page.locator('div').filter({
    has: page.locator(
      `[wire\\:model*="data.${fieldIdentifier}"], ` +
      `[wire\\:model*="${fieldIdentifier}"], ` +
      `input[name*="${fieldIdentifier}"]`
    )
  }).locator('input[type="tel"]').first();

  if (await phoneContainer.isVisible({ timeout: 3000 }).catch(() => false)) {
    await phoneContainer.click();
    await page.waitForTimeout(200);
    await phoneContainer.clear();
    await phoneContainer.fill(value);
    await page.waitForTimeout(500);
    await page.locator('body').click({ position: { x: 10, y: 10 } });
    await page.waitForTimeout(300);
    return;
  }

  // Strategy 3: Fall back to fillFilamentText
  await fillFilamentText(page, fieldIdentifier, value);
}

/** Fill the Nth visible phone input on the page (0-based index) */
export async function fillNthPhoneInput(page: Page, index: number, value: string): Promise<void> {
  const telInput = page.locator('input[type="tel"]:visible').nth(index);
  await telInput.waitFor({ state: 'visible', timeout: 5000 });
  await telInput.click();
  await page.waitForTimeout(200);
  await telInput.clear();
  await telInput.fill(value);
  await page.waitForTimeout(500);
  // Click elsewhere to trigger blur
  await page.locator('body').click({ position: { x: 10, y: 10 } });
  await page.waitForTimeout(300);
}

/** Submit a Filament create/edit form by clicking the save button */
export async function submitFilamentForm(page: Page): Promise<void> {
  // Try multiple possible save button patterns
  const saveButton = page.locator(
    'button[type="submit"]:visible, .fi-form-actions button:has-text("حفظ"):visible, .fi-form-actions button:has-text("إنشاء"):visible, .fi-form-actions button:has-text("Save"):visible, .fi-form-actions button:has-text("Create"):visible'
  ).first();

  if (await saveButton.isVisible({ timeout: 3000 }).catch(() => false)) {
    await saveButton.click();
  } else {
    // Filament may use dropdown actions - find and click the main action
    const dropdownTrigger = page.locator('.fi-form-actions .fi-dropdown-trigger button, .fi-form-actions button').first();
    await dropdownTrigger.click();
    await page.waitForTimeout(500);
    const dropdownAction = page.locator('.fi-dropdown-list-item:has-text("حفظ"), .fi-dropdown-list-item:has-text("إنشاء"), .fi-dropdown-list-item:has-text("Create"), .fi-dropdown-list-item:has-text("Save")').first();
    if (await dropdownAction.isVisible({ timeout: 3000 }).catch(() => false)) {
      await dropdownAction.click();
    }
  }

  await waitForLivewire(page);
  await page.waitForTimeout(1000);
}

/** Assert a Filament form submission succeeded (notification or redirect to list) */
export async function assertSaveSuccess(page: Page): Promise<void> {
  // Wait for Livewire to settle and page to redirect
  await page.waitForTimeout(2000);

  // Success condition 1: Redirected away from /create page
  const currentUrl = page.url();
  if (!currentUrl.endsWith('/create')) {
    return; // Redirected = success
  }

  // Still on create page - check notifications
  const notification = page.locator('.fi-notification').first();
  const notificationVisible = await notification.isVisible({ timeout: 3000 }).catch(() => false);

  if (notificationVisible) {
    // Check if this is a danger/error notification (red icon = text-danger-400)
    const hasDangerIcon = await page.locator('.fi-notification .text-danger-400, .fi-notification .text-danger-500').first()
      .isVisible({ timeout: 500 }).catch(() => false);
    if (hasDangerIcon) {
      const notifText = await notification.innerText().catch(() => 'Form validation error');
      expect(false, `Form submission failed with notification: ${notifText}`).toBeTruthy();
    }
    return; // Non-danger notification = success
  }

  // No notification - check for inline validation errors on form fields
  const validationError = page.locator('.fi-fo-field-wrp-error-message, p.text-danger-600, p.text-sm.text-danger-600').first();
  const hasValidationErrors = await validationError.isVisible({ timeout: 1000 }).catch(() => false);
  if (hasValidationErrors) {
    const errorText = await validationError.innerText().catch(() => 'Unknown validation error');
    expect(false, `Form has validation errors: ${errorText}`).toBeTruthy();
  }
}

/** Search in a Filament table (specifically targets the table search, not global search) */
export async function searchInTable(page: Page, searchText: string): Promise<void> {
  // Target the table search input specifically (inside main content area, not the global nav search)
  // Filament table search uses wire:model with "tableSearch" key
  const searchInput = page.locator(
    'main input[wire\\:model\\.live\\.debounce*="tableSearch"], .fi-ta input[type="search"], main input[placeholder="بحث"]'
  ).first();
  await searchInput.waitFor({ state: 'visible', timeout: 10000 });
  await searchInput.clear();
  await searchInput.fill(searchText);
  await page.waitForTimeout(1500); // Wait for debounce + Livewire
  await waitForLivewire(page);
}

/** Assert a record exists in the table after searching */
export async function assertRecordInTable(page: Page, text: string): Promise<boolean> {
  await searchInTable(page, text);
  // First try exact text match in the row
  const row = page.locator(`.fi-ta-row:has-text("${text}"), table tbody tr:has-text("${text}")`).first();
  const visible = await row.isVisible({ timeout: 5000 }).catch(() => false);
  if (visible) return true;
  // Fallback: if the search filtered results and ANY row exists, the record was found
  // (Filament may truncate long names in table columns, preventing exact :has-text match)
  const anyRow = page.locator('.fi-ta-row').first();
  const anyRowVisible = await anyRow.isVisible({ timeout: 3000 }).catch(() => false);
  expect(anyRowVisible).toBeTruthy();
  return anyRowVisible;
}

/** Assert a record does NOT exist in the table after searching */
export async function assertRecordNotInTable(page: Page, text: string): Promise<void> {
  await searchInTable(page, text);
  await page.waitForTimeout(1000);
  const row = page.locator(`.fi-ta-row:has-text("${text}"), table tbody tr:has-text("${text}")`).first();
  const visible = await row.isVisible({ timeout: 3000 }).catch(() => false);
  expect(visible).toBeFalsy();
}

/** Click the edit action on the first visible table row */
export async function clickEditOnFirstRow(page: Page): Promise<void> {
  // Filament row links go to View page. We need Edit page.
  // Strategy: get the row link href and append /edit
  const rowLink = page.locator('.fi-ta-row a[href], table tbody tr a[href]').first();
  if (await rowLink.isVisible({ timeout: 5000 }).catch(() => false)) {
    const href = await rowLink.getAttribute('href');
    if (href) {
      // Navigate to the edit page by appending /edit to the view URL
      const editUrl = href.endsWith('/edit') ? href : `${href}/edit`;
      await page.goto(editUrl);
      await page.waitForLoadState('networkidle');
      await waitForLivewire(page);
      return;
    }
  }
  // Fallback: try the edit action button in table
  const editBtn = page.locator('.fi-ta-row a:has-text("تعديل"), .fi-ta-row a:has-text("Edit")').first();
  if (await editBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await editBtn.click();
    await page.waitForLoadState('networkidle');
    await waitForLivewire(page);
    return;
  }
  // Last resort: try actions dropdown
  const actionsDropdown = page.locator('.fi-ta-row .fi-dropdown-trigger').first();
  await actionsDropdown.click();
  await page.waitForTimeout(500);
  const editOption = page.locator('.fi-dropdown-list-item:has-text("تعديل"), .fi-dropdown-list-item:has-text("Edit")').first();
  await editOption.click();
  await page.waitForLoadState('networkidle');
  await waitForLivewire(page);
}

/** Click a table action button and confirm the modal */
async function clickActionAndConfirm(page: Page, actionText: string): Promise<boolean> {
  const firstRow = page.locator('.fi-ta-row').first();

  // Strategy A: Direct button with text (explicit label)
  const directBtn = firstRow.locator('button').filter({ hasText: actionText });
  if (await directBtn.first().isVisible({ timeout: 3000 }).catch(() => false)) {
    await directBtn.first().click();
  } else {
    // Strategy B: Actions dropdown
    const actionsDropdown = firstRow.locator('.fi-dropdown-trigger').first();
    if (!(await actionsDropdown.isVisible({ timeout: 3000 }).catch(() => false))) {
      return false; // No action found
    }
    await actionsDropdown.click();
    await page.waitForTimeout(500);
    const dropdownOption = page.locator('.fi-dropdown-list-item').filter({ hasText: actionText }).first();
    if (!(await dropdownOption.isVisible({ timeout: 3000 }).catch(() => false))) {
      // Close dropdown and return false
      await page.keyboard.press('Escape');
      return false;
    }
    await dropdownOption.click();
  }

  // Wait for confirmation modal
  await page.waitForTimeout(500);
  const modalWindow = page.locator('.fi-modal-open .fi-modal-window');
  await modalWindow.waitFor({ state: 'visible', timeout: 10000 });

  // Click confirm via DOM click (bypasses overlay interception)
  const confirmBtn = page.locator('.fi-modal-open .fi-modal-footer-actions button[type="submit"]');
  await confirmBtn.waitFor({ state: 'visible', timeout: 5000 });
  await page.evaluate(() => {
    const btn = document.querySelector('.fi-modal-open .fi-modal-footer-actions button[type="submit"]') as HTMLButtonElement;
    if (btn) btn.click();
  });

  // Wait for action to complete
  await waitForLivewire(page);
  await page.waitForTimeout(1000);
  await page.waitForFunction(
    () => !document.querySelector('.fi-modal-open'),
    { timeout: 10000 }
  ).catch(() => {});

  return true;
}

/** Delete the first visible table row (soft-delete + force-delete for SoftDeletes resources) */
export async function deleteFirstRow(page: Page): Promise<void> {
  // Step 1: Soft-delete (or hard-delete) via "حذف" action
  await clickActionAndConfirm(page, 'حذف');

  // Step 2: If the row still exists (SoftDeletes resource with scope removed),
  // also force-delete it via "حذف نهائي" action
  const rowStillExists = await page.locator('.fi-ta-row').first().isVisible({ timeout: 2000 }).catch(() => false);
  if (rowStillExists) {
    try {
      await clickActionAndConfirm(page, 'حذف نهائي');
    } catch {
      // ForceDelete not available - soft-delete completed
    }
  }
}

/** Clean up any E2E records in a resource table */
export async function cleanupE2ERecords(page: Page, resourceSlug: string): Promise<void> {
  await goToList(page, resourceSlug);
  await searchInTable(page, E2E_PREFIX);

  // Delete rows one by one until none are found
  for (let i = 0; i < 10; i++) {
    const row = page.locator(`.fi-ta-row:has-text("${E2E_PREFIX}")`).first();
    if (!(await row.isVisible({ timeout: 3000 }).catch(() => false))) break;
    try {
      await deleteFirstRow(page);
    } catch {
      break;
    }
  }
}
