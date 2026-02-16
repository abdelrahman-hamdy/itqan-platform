import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire } from '../fixtures/filament.fixture';
import {
  generateE2EName, goToCreate, goToList, fillFilamentText,
  fillFilamentNumber, selectFilamentOption, selectFirstFilamentOption,
  selectFirstSearchableOption, toggleFilamentSwitch, fillPhoneInput,
  submitFilamentForm, assertSaveSuccess, searchInTable,
  assertRecordInTable, assertRecordNotInTable, clickEditOnFirstRow,
  deleteFirstRow, cleanupE2ERecords, E2E_PREFIX
} from '../fixtures/crud.fixture';

const RESOURCE = 'student-profiles';

test.describe.serial('CRUD - Student Profile', () => {
  let recordName: string;
  let uniqueEmail: string;

  test.beforeAll(() => {
    recordName = generateE2EName('Student');
    uniqueEmail = `e2e+student${Date.now()}@test.com`;
  });

  test.afterAll(async ({ adminPage: page }) => {
    try { await cleanupE2ERecords(page, RESOURCE); } catch {}
  });

  test('1. create student profile', async ({ adminPage: page }) => {
    await goToCreate(page, RESOURCE);
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Required: first_name
    await fillFilamentText(page, 'first_name', 'E2E');

    // Required: last_name
    await fillFilamentText(page, 'last_name', recordName);

    // Required: email
    await fillFilamentText(page, 'email', uniqueEmail);

    // Phone: PhoneInput component - use wire:key to find correct field
    try {
      await fillPhoneInput(page, 'phone', '500000002');
    } catch {}

    // Required: nationality (searchable select with preload)
    try {
      await selectFirstSearchableOption(page, 'nationality', 'سعود');
    } catch {
      try { await selectFirstFilamentOption(page, 'nationality'); } catch {}
    }

    // Gender (optional but fill for completeness)
    try {
      await selectFirstFilamentOption(page, 'gender');
    } catch {
      try { await selectFirstSearchableOption(page, 'gender'); } catch {}
    }

    // Scroll down to reveal academic + emergency sections
    await page.evaluate(() => window.scrollBy(0, 600));
    await page.waitForTimeout(500);

    // Required: grade_level_id (searchable relationship select with preload)
    try {
      await selectFirstSearchableOption(page, 'grade_level_id');
    } catch {}

    // Required: parent_phone (PhoneInput) - use wire:key to find correct field
    try {
      await fillPhoneInput(page, 'parent_phone', '500000001');
    } catch {}

    // Scroll to bottom for submit button
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(500);

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('2. verify student profile in list', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('3. edit student profile', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await clickEditOnFirstRow(page);
    await assertNoServerError(page);

    recordName = recordName + ' (edited)';
    await fillFilamentText(page, 'last_name', recordName);

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('4. verify edited student profile', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('5. delete student profile', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await deleteFirstRow(page);
  });

  test('6. verify student profile deleted', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordNotInTable(page, recordName);
  });
});
