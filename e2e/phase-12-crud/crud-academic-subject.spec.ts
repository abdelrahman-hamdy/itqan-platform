import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire } from '../fixtures/filament.fixture';
import {
  generateE2EName, goToCreate, goToList, fillFilamentText,
  fillFilamentNumber, selectFilamentOption, toggleFilamentSwitch,
  submitFilamentForm, assertSaveSuccess, searchInTable,
  assertRecordInTable, assertRecordNotInTable, clickEditOnFirstRow,
  deleteFirstRow, cleanupE2ERecords, E2E_PREFIX
} from '../fixtures/crud.fixture';

const RESOURCE = 'academic-subjects';

test.describe.serial('CRUD - Academic Subject', () => {
  let recordName: string;

  test.beforeAll(() => {
    recordName = generateE2EName('Subject');
  });

  test.afterAll(async ({ adminPage: page }) => {
    try { await cleanupE2ERecords(page, RESOURCE); } catch {}
  });

  test('1. create subject', async ({ adminPage: page }) => {
    await goToCreate(page, RESOURCE);
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    await fillFilamentText(page, 'name', recordName);

    // Toggle is_active if present
    try {
      await toggleFilamentSwitch(page, 'is_active', true);
    } catch {}

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('2. verify subject in list', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('3. edit subject', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await clickEditOnFirstRow(page);
    await assertNoServerError(page);

    recordName = recordName + ' (edited)';
    await fillFilamentText(page, 'name', recordName);

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('4. verify edited subject', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('5. delete subject', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await deleteFirstRow(page);
  });

  test('6. verify subject deleted', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordNotInTable(page, recordName);
  });
});
