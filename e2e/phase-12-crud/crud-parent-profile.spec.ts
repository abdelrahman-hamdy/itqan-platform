import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire } from '../fixtures/filament.fixture';
import {
  generateE2EName, goToCreate, goToList, fillFilamentText,
  fillFilamentNumber, selectFilamentOption, selectFirstFilamentOption,
  toggleFilamentSwitch,
  submitFilamentForm, assertSaveSuccess, searchInTable,
  assertRecordInTable, assertRecordNotInTable, clickEditOnFirstRow,
  deleteFirstRow, cleanupE2ERecords, E2E_PREFIX
} from '../fixtures/crud.fixture';

const RESOURCE = 'parent-profiles';

test.describe.serial('CRUD - Parent Profile', () => {
  let recordName: string;
  let uniqueEmail: string;

  test.beforeAll(() => {
    recordName = generateE2EName('Parent');
    uniqueEmail = `e2e+parent${Date.now()}@test.com`;
  });

  test.afterAll(async ({ adminPage: page }) => {
    try { await cleanupE2ERecords(page, RESOURCE); } catch {}
  });

  test('1. create parent profile', async ({ adminPage: page }) => {
    await goToCreate(page, RESOURCE);
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Required: email
    await fillFilamentText(page, 'email', uniqueEmail);

    // Required: first_name
    await fillFilamentText(page, 'first_name', 'E2E');

    // Required: last_name
    await fillFilamentText(page, 'last_name', recordName);

    // Required: phone (PhoneInput component - try multiple approaches)
    // PhoneInput may render as input[type="tel"] with wire:model
    try {
      await fillFilamentText(page, 'phone', '0500000002');
    } catch {
      // Fallback: try finding tel input directly
      try {
        const telInput = page.locator('input[type="tel"]').first();
        await telInput.fill('0500000002');
        await page.waitForTimeout(300);
      } catch {}
    }

    // Optional: password (may be auto-generated)
    try {
      await fillFilamentText(page, 'password', 'E2ETest@2026');
    } catch {}

    try {
      await fillFilamentText(page, 'password_confirmation', 'E2ETest@2026');
    } catch {}

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('2. verify parent profile in list', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('3. edit parent profile', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await clickEditOnFirstRow(page);
    await assertNoServerError(page);

    recordName = recordName + ' (edited)';
    await fillFilamentText(page, 'last_name', recordName);

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('4. verify edited parent profile', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('5. delete parent profile', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await deleteFirstRow(page);
  });

  test('6. verify parent profile deleted', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordNotInTable(page, recordName);
  });
});
