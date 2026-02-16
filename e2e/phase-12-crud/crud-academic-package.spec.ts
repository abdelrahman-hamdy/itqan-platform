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

const RESOURCE = 'academic-packages';

test.describe.serial('CRUD - Academic Package', () => {
  let recordName: string;

  test.beforeAll(() => {
    recordName = generateE2EName('AcadPkg');
  });

  test.afterAll(async ({ adminPage: page }) => {
    try { await cleanupE2ERecords(page, RESOURCE); } catch {}
  });

  test('1. create academic package', async ({ adminPage: page }) => {
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
      try { await fillFilamentNumber(page, 'session_duration_minutes', 60); } catch {}
    }

    // Required: monthly_price
    await fillFilamentNumber(page, 'monthly_price', 150);

    // Required: quarterly_price
    await fillFilamentNumber(page, 'quarterly_price', 400);

    // Required: yearly_price
    await fillFilamentNumber(page, 'yearly_price', 1400);

    // Optional: description
    try {
      await fillFilamentText(page, 'description', 'E2E test academic tutoring package');
    } catch {}

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('2. verify academic package in list', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('3. edit academic package', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await clickEditOnFirstRow(page);
    await assertNoServerError(page);

    recordName = recordName + ' (edited)';
    await fillFilamentText(page, 'name', recordName);

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('4. verify edited academic package', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('5. delete academic package', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await deleteFirstRow(page);
  });

  test('6. verify academic package deleted', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordNotInTable(page, recordName);
  });
});
