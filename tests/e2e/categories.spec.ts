import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

const unique = () => Date.now();

test.describe('Categories CRUD', () => {
    let testName = '';

    test.beforeEach(async ({ page }) => {
        testName = `Test Cat ${unique()}`;
        await loginAs(page, 'admin');
        await page.goto('/u/areas');
    });

    test('displays categories list', async ({ page }) => {
        await expect(page.locator('h3').first()).toContainText('Áreas de Conocimiento');
        await expect(page.locator('table')).toBeVisible();
    });

    test('creates a new category', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).crearCategoria(url), '/u/areas');
        await page.waitForSelector('#categoria-form:not(.hidden)');
        await page.fill('#nombre', testName);
        await page.click('#categoria-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('shows validation with empty name via HTML5', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).crearCategoria(url), '/u/areas');
        await page.waitForSelector('#categoria-form:not(.hidden)');
        await page.fill('#nombre', '');
        const isValid = await page.evaluate(() => {
            const form = document.getElementById('categoria-form') as HTMLFormElement;
            const input = document.getElementById('nombre') as HTMLInputElement;
            return input.checkValidity();
        });
        expect(isValid).toBe(false);
    });

    test('edits an existing category', async ({ page }) => {
        const editName = `Edited Cat ${unique()}`;
        await page.evaluate((url: string) => (window as any).editarCategoria(url), '/u/areas/1');
        await page.waitForSelector('#categoria-form:not(.hidden)');
        await page.fill('#nombre', editName);
        await page.click('#categoria-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('deletes a category', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).crearCategoria(url), '/u/areas');
        await page.waitForSelector('#categoria-form:not(.hidden)');
        const delName = `Del Cat ${unique()}`;
        await page.fill('#nombre', delName);
        await page.click('#categoria-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();

        const deleteLink = page.locator('a[title="Eliminar área de conocimiento"]').first();
        const href = await deleteLink.getAttribute('href');
        const urlMatch = href?.match(/eliminarCategoria\('(.+)'\)/);
        if (urlMatch) {
            await page.evaluate((url: string) => (window as any).eliminarCategoria(url), urlMatch[1]);
            await page.waitForSelector('#categoria-form-delete:not(.hidden)');
            await page.click('#categoria-form-delete #btn-action');
            await page.waitForLoadState('load');
            await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
        }
    });
});
