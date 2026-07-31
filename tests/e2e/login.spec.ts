import { test, expect } from '@playwright/test';

test.describe('Login', () => {
    test('logs in successfully with valid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.fill('#email', 'admin@pdvsa.com');
        await page.fill('#password', '123456');

        await page.click('button[type="submit"]');

        await expect(page).toHaveURL(/home/);
    });
});
