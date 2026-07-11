import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
dotenv.config({ path: path.join(__dirname, '.env') });

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://gorev.test';
const slowMo = Number(process.env.PLAYWRIGHT_SLOW_MO ?? '0');

export default defineConfig({
  testDir: './tests',
  timeout: 300_000,
  expect: { timeout: 20_000 },
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL,
    trace: 'on',
    video: 'on',
    screenshot: 'on',
    launchOptions: { slowMo },
    locale: 'tr-TR',
    actionTimeout: 25_000,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
