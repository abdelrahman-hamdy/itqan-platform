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
