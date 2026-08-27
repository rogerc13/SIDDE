import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30000,
    expect: {
        timeout: 5000,
    },
    fullyParallel: false,
    retries: 1,
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://sidde.test',
        browserName: 'chromium',
        headless: process.env.CI === 'true',
        screenshot: 'only-on-failure',
        trace: 'on-first-retry',
    },
});
