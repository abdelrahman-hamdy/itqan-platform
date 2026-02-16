import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './e2e',
  timeout: 60_000,
  expect: { timeout: 10_000 },
  fullyParallel: false,
  retries: 1,
  workers: 3,
  reporter: [
    ['html', { outputFolder: 'e2e/reports' }],
    ['list'],
    ['json', { outputFile: 'e2e/reports/results.json' }],
  ],
  use: {
    locale: 'ar',
    timezoneId: 'Asia/Riyadh',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    video: 'retain-on-failure',
    actionTimeout: 15_000,
    navigationTimeout: 30_000,
  },
  projects: [
    { name: 'setup', testDir: './e2e/setup', testMatch: 'global-setup.ts' },
    {
      name: 'public',
      testDir: './e2e/phase-01-public',
      use: { ...devices['Desktop Chrome'], baseURL: 'https://itqanway.com' },
    },
    {
      name: 'auth',
      testDir: './e2e/phase-02-auth',
      use: { ...devices['Desktop Chrome'], baseURL: 'https://itqan-academy.itqanway.com' },
    },
    {
      name: 'admin',
      testDir: './e2e/phase-03-admin',
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'https://itqanway.com',
        storageState: 'e2e/auth/superadmin.json',
      },
    },
    {
      name: 'student',
      testDir: './e2e/phase-04-student',
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'https://itqan-academy.itqanway.com',
        storageState: 'e2e/auth/student.json',
      },
    },
    {
      name: 'teacher',
      testDir: './e2e/phase-05-teacher',
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'https://itqan-academy.itqanway.com',
        storageState: 'e2e/auth/quran-teacher.json',
      },
    },
    {
      name: 'supervisor',
      testDir: './e2e/phase-06-supervisor',
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'https://itqan-academy.itqanway.com',
        storageState: 'e2e/auth/supervisor.json',
      },
    },
    {
      name: 'parent',
      testDir: './e2e/phase-07-parent',
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'https://itqan-academy.itqanway.com',
        storageState: 'e2e/auth/parent.json',
      },
    },
    {
      name: 'chat',
      testDir: './e2e/phase-08-chat',
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'https://itqan-academy.itqanway.com',
        storageState: 'e2e/auth/student.json',
      },
    },
    {
      name: 'payments',
      testDir: './e2e/phase-09-payments',
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'https://itqan-academy.itqanway.com',
        storageState: 'e2e/auth/student.json',
      },
    },
    {
      name: 'consistency',
      testDir: './e2e/phase-10-consistency',
      dependencies: ['setup'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'regression',
      testDir: './e2e/phase-11-regression',
      dependencies: ['setup'],
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'crud',
      testDir: './e2e/phase-12-crud',
      dependencies: ['setup'],
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'https://itqanway.com',
        storageState: 'e2e/auth/superadmin.json',
      },
    },
    {
      name: 'api',
      testDir: './e2e/phase-13-api',
      timeout: 90_000,
      workers: 1,
      retries: 2,
      use: {
        // API tests use axios directly - no browser needed
        screenshot: 'off',
        trace: 'off',
        video: 'off',
      },
    },
  ],
});
