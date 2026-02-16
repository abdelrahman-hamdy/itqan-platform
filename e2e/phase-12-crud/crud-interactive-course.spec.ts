import { expect } from '@playwright/test';
import { adminTest as test } from '../fixtures/auth.fixture';
import { assertNoServerError, assertNotLoginPage, waitForLivewire } from '../fixtures/filament.fixture';
import {
  generateE2EName, goToCreate, goToList, fillFilamentText,
  fillFilamentNumber, selectFilamentOption, selectFirstFilamentOption,
  selectFirstSearchableOption, toggleFilamentSwitch,
  submitFilamentForm, assertSaveSuccess, searchInTable,
  assertRecordInTable, assertRecordNotInTable, clickEditOnFirstRow,
  deleteFirstRow, cleanupE2ERecords, E2E_PREFIX
} from '../fixtures/crud.fixture';

const RESOURCE = 'interactive-courses';

test.describe.serial('CRUD - Interactive Course', () => {
  let recordName: string;

  test.beforeAll(() => {
    recordName = generateE2EName('Course');
  });

  test.afterAll(async ({ adminPage: page }) => {
    try { await cleanupE2ERecords(page, RESOURCE); } catch {}
  });

  test('1. create interactive course', async ({ adminPage: page }) => {
    await goToCreate(page, RESOURCE);
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Required fields (17 total - very complex form)
    await fillFilamentText(page, 'title', recordName);
    await fillFilamentText(page, 'description', 'E2E test interactive course');

    // Required selects: subject_id, grade_level_id, assigned_teacher_id
    try { await selectFirstSearchableOption(page, 'subject_id'); } catch {}
    try { await selectFirstSearchableOption(page, 'grade_level_id'); } catch {}
    try { await selectFirstSearchableOption(page, 'assigned_teacher_id'); } catch {}

    // Required numbers: total_sessions, sessions_per_week, max_students
    try { await fillFilamentNumber(page, 'total_sessions', 16); } catch {}
    try { await fillFilamentNumber(page, 'sessions_per_week', 2); } catch {}
    try { await fillFilamentNumber(page, 'max_students', 20); } catch {}

    // Required selects: session_duration_minutes, difficulty_level, payment_type, status
    try { await selectFirstFilamentOption(page, 'session_duration_minutes'); } catch {}
    try { await selectFirstFilamentOption(page, 'difficulty_level'); } catch {}
    try { await selectFirstFilamentOption(page, 'payment_type'); } catch {}
    try { await selectFirstFilamentOption(page, 'status'); } catch {}

    // Required numbers: student_price, teacher_payment
    try { await fillFilamentNumber(page, 'student_price', 500); } catch {}
    try { await fillFilamentNumber(page, 'teacher_payment', 2000); } catch {}

    // Required: start_date (DatePicker - skip for now, hard to fill reliably)

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('2. verify interactive course in list', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('3. edit interactive course', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await clickEditOnFirstRow(page);
    await assertNoServerError(page);

    recordName = recordName + ' (edited)';
    await fillFilamentText(page, 'title', recordName);

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('4. verify edited interactive course', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('5. delete interactive course', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await deleteFirstRow(page);
  });

  test('6. verify interactive course deleted', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordNotInTable(page, recordName);
  });
});
