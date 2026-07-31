import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

test.describe('Profile (Mis Datos)', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/mis_datos');
    });

    test('displays profile form with pre-filled data', async ({ page }) => {
        await expect(page.locator('h2')).toContainText('Mis datos');
        await expect(page.locator('#nombre')).not.toBeEmpty();
        await expect(page.locator('#email')).not.toBeEmpty();
    });

    test('updates phone number', async ({ page }) => {
        const newPhone = `${Date.now()}`;
        await page.fill('#phone', newPhone);
        await page.click('button:has-text("Guardar Cambios")');

        await expect(page.locator('.alert')).toBeVisible();
    });

    test('updates password', async ({ page }) => {
        await page.fill('#password', 'newpassword123');
        await page.fill('#password_confirmation', 'newpassword123');
        await page.click('button:has-text("Guardar Cambios")');

        await expect(page.locator('.alert')).toBeVisible();

        await page.goto('/u/mis_datos');
        await page.fill('#password', '123456');
        await page.fill('#password_confirmation', '123456');
        await page.click('button:has-text("Guardar Cambios")');
    });

    test('shows error with mismatched passwords', async ({ page }) => {
        await page.fill('#password', 'password123');
        await page.fill('#password_confirmation', 'differentpassword');
        await page.click('button:has-text("Guardar Cambios")');

        await expect(page.locator('.callout-danger')).toBeVisible();
    });
});
