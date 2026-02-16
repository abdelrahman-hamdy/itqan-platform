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

const RESOURCE = 'quran-circles';

test.describe.serial('CRUD - Quran Circle', () => {
  let recordName: string;

  test.beforeAll(() => {
    recordName = generateE2EName('Circle');
  });

  test.afterAll(async ({ adminPage: page }) => {
    try { await cleanupE2ERecords(page, RESOURCE); } catch {}
  });

  test('1. create quran circle', async ({ adminPage: page }) => {
    await goToCreate(page, RESOURCE);
    await assertNotLoginPage(page);
    await assertNoServerError(page);

    // Required: name
    await fillFilamentText(page, 'name', recordName);

    // Required: age_group (native select)
    try {
      await selectFirstFilamentOption(page, 'age_group');
    } catch {
      try { await selectFirstSearchableOption(page, 'age_group'); } catch {}
    }
    await page.waitForTimeout(300);

    // Required: gender_type (native select)
    try {
      await selectFirstFilamentOption(page, 'gender_type');
    } catch {
      try { await selectFirstSearchableOption(page, 'gender_type'); } catch {}
    }
    await page.waitForTimeout(300);

    // specialization (default='memorization') - should be pre-filled
    // memorization_level (default='beginner') - should be pre-filled
    // monthly_sessions_count (default=8) - should be pre-filled

    // Scroll down to reveal teacher select and other settings
    await page.evaluate(() => window.scrollBy(0, 500));
    await page.waitForTimeout(500);

    // Required: quran_teacher_id (searchable relationship select with preload)
    try {
      await selectFirstSearchableOption(page, 'quran_teacher_id', 'معلم');
    } catch {
      try {
        // Try without search term - just pick the first available option
        await selectFirstSearchableOption(page, 'quran_teacher_id');
      } catch {
        // Last resort: try clicking any visible combobox trigger in the teacher section
        try {
          const teacherSection = page.locator('.fi-fo-select').filter({
            has: page.locator('button, [role="combobox"]')
          });
          const count = await teacherSection.count();
          // Try the last unselected combobox (teacher is usually after age_group/gender which are native selects)
          for (let i = 0; i < count; i++) {
            const container = teacherSection.nth(i);
            const trigger = container.locator('button, [role="combobox"]').first();
            if (await trigger.isVisible({ timeout: 1000 }).catch(() => false)) {
              const selectedText = await trigger.innerText().catch(() => '');
              if (selectedText.includes('اختر') || selectedText.trim() === '') {
                await trigger.click();
                await page.waitForTimeout(800);
                const option = page.locator('[role="option"]').first();
                if (await option.isVisible({ timeout: 3000 }).catch(() => false)) {
                  await option.click();
                  await page.waitForTimeout(300);
                  break;
                }
                await page.keyboard.press('Escape');
              }
            }
          }
        } catch {}
      }
    }

    // Required: max_students (number, default=8) - may be pre-filled
    try {
      await fillFilamentNumber(page, 'max_students', 10);
    } catch {}

    // Required: monthly_fee (number)
    await fillFilamentNumber(page, 'monthly_fee', 200);

    // Scroll to bottom for submit button
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(500);

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('2. verify quran circle in list', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('3. edit quran circle', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await clickEditOnFirstRow(page);
    await assertNoServerError(page);

    recordName = recordName + ' (edited)';
    await fillFilamentText(page, 'name', recordName);

    await submitFilamentForm(page);
    await assertSaveSuccess(page);
  });

  test('4. verify edited quran circle', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordInTable(page, recordName);
  });

  test('5. delete quran circle', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await searchInTable(page, recordName);
    await deleteFirstRow(page);
  });

  test('6. verify quran circle deleted', async ({ adminPage: page }) => {
    await goToList(page, RESOURCE);
    await assertRecordNotInTable(page, recordName);
  });
});
