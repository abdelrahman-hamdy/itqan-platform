import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire } from '../fixtures/filament.fixture';
import {
  generateE2EName, goToCreate, goToList, fillFilamentText,
  fillFilamentNumber, selectFilamentOption, selectFirstFilamentOption, toggleFilamentSwitch,
  submitFilamentForm, assertSaveSuccess, searchInTable,
  assertRecordInTable, assertRecordNotInTable, clickEditOnFirstRow,
  deleteFirstRow, cleanupE2ERecords, E2E_PREFIX
} from '../fixtures/crud.fixture';

const RESOURCE = 'quran-packages';

test.describe.serial('CRUD - Quran Package', () => {
  let recordName: string;

  test.beforeAll(() => {
    recordName = generateE2EName('Package');
  });

  test.afterAll(async ({ adminPage: page }) => {
    try { await cleanupE2ERecords(page, RESOURCE); } catch {}
  });

  test('1. create quran package', async ({ adminPage: page }) => {
    await goToCreate(page, RESOURCE);
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Required: name
    await fillFilamentText(page, 'name', recordName);

    // Required: sessions_per_month (number, default=8)
    await fillFilamentNumber(page, 'sessions_per_month', 8);

    // Required: session_duration_minutes (Select with SessionDuration enum)
    try {
      await selectFirstFilamentOption(page, 'session_duration_minutes');
    } catch {
      // Try as number input if not a select
      try { await fillFilamentNumber(page, 'session_duration_minutes', 45); } catch {}
    }

    // Required: monthly_price
    await fillFilamentNumber(page, 'monthly_price', 100);

    // Required: quarterly_price
    await fillFilamentNumber(page, 'quarterly_price', 250);

    // Required: yearly_price
    await fillFilamentNumber(page, 'yearly_price', 900);

    // Optional: description
    try {
      await fillFilamentText(page, 'description', 'E2E test package for Quran memorization');
    } catch {}

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('2. verify quran package in list', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('3. edit quran package', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await clickEditOnFirstRow(page);
    await assertNoServerError(page);

    recordName = recordName + ' (edited)';
    await fillFilamentText(page, 'name', recordName);

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('4. verify edited quran package', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('5. delete quran package', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await deleteFirstRow(page);
  });

  test('6. verify quran package deleted', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordNotInTable(page, recordName);
  });
});
