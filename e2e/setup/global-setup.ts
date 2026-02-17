import { test as setup, expect } from '@playwright/test';
import { TEST_ACCOUNTS, loginViaUI, loginAsFilamentAdmin, loginAsFilamentPanel } from '../fixtures/auth.fixture';

const ACADEMY_URL = 'https://e2e-test.itqanway.com';
const ADMIN_URL = 'https://itqanway.com';

setup('authenticate as superadmin', async ({ page }) => {
  await loginAsFilamentAdmin(page, TEST_ACCOUNTS.superadmin.email, TEST_ACCOUNTS.superadmin.password);
  expect(page.url()).not.toContain('/login');
  await page.context().storageState({ path: 'e2e/auth/superadmin.json' });
});

setup('authenticate as student', async ({ page }) => {
  await loginViaUI(page, TEST_ACCOUNTS.student.email, TEST_ACCOUNTS.student.password);
  await page.context().storageState({ path: 'e2e/auth/student.json' });
});

setup('authenticate as quran teacher', async ({ page }) => {
  await loginAsFilamentPanel(page, 'teacher-panel', TEST_ACCOUNTS['quran-teacher'].email, TEST_ACCOUNTS['quran-teacher'].password);
  await expect(page).toHaveURL(/\/teacher-panel/);
  await page.context().storageState({ path: 'e2e/auth/quran-teacher.json' });
});

setup('authenticate as supervisor', async ({ page }) => {
  await loginAsFilamentPanel(page, 'supervisor-panel', TEST_ACCOUNTS.supervisor.email, TEST_ACCOUNTS.supervisor.password);
  await expect(page).toHaveURL(/\/supervisor-panel/);
  await page.context().storageState({ path: 'e2e/auth/supervisor.json' });
});

setup('authenticate as academic teacher', async ({ page }) => {
  await loginAsFilamentPanel(page, 'academic-teacher-panel', TEST_ACCOUNTS['academic-teacher'].email, TEST_ACCOUNTS['academic-teacher'].password);
  await expect(page).toHaveURL(/\/academic-teacher-panel/);
  await page.context().storageState({ path: 'e2e/auth/academic-teacher.json' });
});

setup('authenticate as parent', async ({ page }) => {
  await loginViaUI(page, TEST_ACCOUNTS.parent.email, TEST_ACCOUNTS.parent.password);
  expect(page.url()).not.toContain('/login');
  await page.context().storageState({ path: 'e2e/auth/parent.json' });
});
