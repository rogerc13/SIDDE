import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

test.describe('Authentication', () => {
    test.describe('Login', () => {
        test('logs in successfully with valid credentials', async ({ page }) => {
            await loginAs(page, 'admin');
            await expect(page).toHaveURL(/home/);
        });

        test('shows error with wrong password', async ({ page }) => {
            await page.goto('/login');
            await page.fill('#email', 'admin@pdvsa.com');
            await page.fill('#password', 'wrongpassword');
            await page.click('button[type="submit"]');
            await expect(page).toHaveURL(/login/);
        });

        test('shows error with non-existent email', async ({ page }) => {
            await page.goto('/login');
            await page.fill('#email', 'nonexistent@pdvsa.com');
            await page.fill('#password', '123456');
            await page.click('button[type="submit"]');
            await expect(page).toHaveURL(/login/);
        });

        test('redirects to home if already authenticated', async ({ page }) => {
            await loginAs(page, 'admin');
            await page.goto('/login');
            await expect(page).toHaveURL(/home/);
        });
    });

    test.describe('Logout', () => {
        test('logs out and clears session', async ({ page }) => {
            await loginAs(page, 'admin');
            await page.evaluate(() => {
                (document.getElementById('logout-form') as HTMLFormElement).submit();
            });
            await page.waitForURL('**/login');
            await expect(page).toHaveURL(/login/);
            await page.goto('/home');
            await expect(page).toHaveURL(/login/);
        });
    });

    test.describe('Protected Routes', () => {
        test('redirects to login when accessing protected route without auth', async ({ page }) => {
            await page.goto('/home');
            await expect(page).toHaveURL(/login/);
        });
    });
});
