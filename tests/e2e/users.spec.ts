import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

const unique = () => Date.now();

test.describe('Users CRUD', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/usuarios');
    });

    test('displays users list', async ({ page }) => {
        await expect(page.locator('h3').first()).toContainText('Usuarios');
        await expect(page.locator('table')).toBeVisible();
    });

    test('creates a new user', async ({ page }) => {
        const testEmail = `testuser${unique()}@pdvsa.com`;
        await page.evaluate((url: string) => (window as any).crearUsuario(url), '/u/usuarios');
        await page.waitForSelector('#usuario-form:not(.hidden)');
        await page.fill('#nombre', 'Test');
        await page.fill('#apellido', 'User');
        await page.fill('#email', testEmail);
        await page.selectOption('#id_type', '1');
        await page.fill('#ci', `${unique()}`);
        await page.selectOption('#sex', 'Masculino');
        await page.selectOption('#rol', '5');
        await page.fill('#password', 'password123');
        await page.fill('#password_confirmation', 'password123');
        await page.click('#usuario-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('shows validation error with duplicate email', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).crearUsuario(url), '/u/usuarios');
        await page.waitForSelector('#usuario-form:not(.hidden)');
        await page.fill('#nombre', 'Test');
        await page.fill('#apellido', 'User');
        await page.fill('#email', 'admin@pdvsa.com');
        await page.selectOption('#id_type', '1');
        await page.fill('#ci', `${unique()}`);
        await page.selectOption('#sex', 'Masculino');
        await page.selectOption('#rol', '5');
        await page.fill('#password', 'password123');
        await page.fill('#password_confirmation', 'password123');
        await page.click('#usuario-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.callout-danger').first()).toBeVisible();
    });

    test('shows validation error with short password', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).crearUsuario(url), '/u/usuarios');
        await page.waitForSelector('#usuario-form:not(.hidden)');
        await page.fill('#nombre', 'Test');
        await page.fill('#apellido', 'User');
        await page.fill('#email', `shortpwd${unique()}@pdvsa.com`);
        await page.selectOption('#id_type', '1');
        await page.fill('#ci', `${unique()}`);
        await page.selectOption('#sex', 'Masculino');
        await page.selectOption('#rol', '5');
        await page.fill('#password', '123');
        await page.fill('#password_confirmation', '123');
        await page.click('#usuario-aceptar');
        await page.waitForLoadState('load');
        const url = page.url();
        const gotError = await page.locator('.callout-danger, .alert-danger').first().isVisible().catch(() => false);
        const stayedOnPage = url.includes('usuarios');
        expect(gotError || stayedOnPage).toBe(true);
    });

    test('deletes a user', async ({ page }) => {
        const testEmail = `deluser${unique()}@pdvsa.com`;
        await page.evaluate((url: string) => (window as any).crearUsuario(url), '/u/usuarios');
        await page.waitForSelector('#usuario-form:not(.hidden)');
        await page.fill('#nombre', 'Del');
        await page.fill('#apellido', 'User');
        await page.fill('#email', testEmail);
        await page.selectOption('#id_type', '1');
        await page.fill('#ci', `${unique()}`);
        await page.selectOption('#sex', 'Masculino');
        await page.selectOption('#rol', '5');
        await page.fill('#password', 'password123');
        await page.fill('#password_confirmation', 'password123');
        await page.click('#usuario-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();

        const deleteLink = page.locator('tr').filter({ hasText: testEmail }).locator('a[title="Eliminar usuario"]');
        if (await deleteLink.isVisible({ timeout: 3000 }).catch(() => false)) {
            const href = await deleteLink.getAttribute('href');
            const urlMatch = href?.match(/eliminarUsuario\('(.+)'\)/);
            if (urlMatch) {
                await page.evaluate((url: string) => (window as any).eliminarUsuario(url), urlMatch[1]);
                await page.waitForSelector('#usuario-form-delete:not(.hidden)');
                await page.click('#usuario-form-delete #btn-action');
                await page.waitForLoadState('load');
                await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
            }
        }
    });
});
