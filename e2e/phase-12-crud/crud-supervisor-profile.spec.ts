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

const RESOURCE = 'supervisor-profiles';

test.describe.serial('CRUD - Supervisor Profile', () => {
  let recordName: string;
  let uniqueEmail: string;

  test.beforeAll(() => {
    recordName = generateE2EName('Supervisor');
    uniqueEmail = `e2e+supervisor${Date.now()}@test.com`;
  });

  test.afterAll(async ({ adminPage: page }) => {
    try { await cleanupE2ERecords(page, RESOURCE); } catch {}
  });

  test('1. create supervisor profile', async ({ adminPage: page }) => {
    await goToCreate(page, RESOURCE);
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Required: email
    await fillFilamentText(page, 'email', uniqueEmail);

    // Required: first_name
    await fillFilamentText(page, 'first_name', 'E2E');

    // Required: last_name
    await fillFilamentText(page, 'last_name', recordName);

    // Required: phone (PhoneInput component)
    try {
      await fillFilamentText(page, 'phone', '0500000003');
    } catch {
      try {
        const telInput = page.locator('input[type="tel"]').first();
        await telInput.fill('0500000003');
        await page.waitForTimeout(300);
      } catch {}
    }

    // Required: gender (native select)
    try {
      await selectFirstFilamentOption(page, 'gender');
    } catch {}

    // Required: password (for create)
    await fillFilamentText(page, 'password', 'E2ETest@2026');
    await fillFilamentText(page, 'password_confirmation', 'E2ETest@2026');

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('2. verify supervisor profile in list', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('3. edit supervisor profile', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await clickEditOnFirstRow(page);
    await assertNoServerError(page);

    recordName = recordName + ' (edited)';
    await fillFilamentText(page, 'last_name', recordName);

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('4. verify edited supervisor profile', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('5. delete supervisor profile', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await deleteFirstRow(page);
  });

  test('6. verify supervisor profile deleted', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordNotInTable(page, recordName);
  });
});
