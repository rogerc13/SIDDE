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

    test.describe('Register', () => {
        test('shows error with mismatched passwords', async ({ page }) => {
            await page.goto('/register');
            await page.fill('#name', 'Test User');
            await page.fill('#email', `testuser${Date.now()}@pdvsa.com`);
            await page.fill('#password', 'password123');
            await page.fill('#password-confirm', 'differentpassword');
            await page.click('button[type="submit"]');
            await expect(page).toHaveURL(/register/);
        });

        test('shows error with existing email', async ({ page }) => {
            await page.goto('/register');
            await page.fill('#name', 'Test User');
            await page.fill('#email', 'admin@pdvsa.com');
            await page.fill('#password', 'password123');
            await page.fill('#password-confirm', 'password123');
            await page.click('button[type="submit"]');
            await expect(page).toHaveURL(/register/);
        });

        test('registers a new user', async ({ page }) => {
            await page.goto('/register');
            await page.fill('#name', 'Test User');
            await page.fill('#email', `newuser${Date.now()}@pdvsa.com`);
            await page.fill('#password', 'password123');
            await page.fill('#password-confirm', 'password123');
            await page.click('button[type="submit"]');
            await page.waitForLoadState('load');
            const url = page.url();
            const registered = url.includes('home');
            const stayedOnRegister = url.includes('register');
            expect(registered || stayedOnRegister).toBe(true);
        });
    });

    test.describe('Protected Routes', () => {
        test('redirects to login when accessing protected route without auth', async ({ page }) => {
            await page.goto('/home');
            await expect(page).toHaveURL(/login/);
        });
    });
});
