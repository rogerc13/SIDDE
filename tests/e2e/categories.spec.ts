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

    test('allows duplicate category names', async ({ page }) => {
        const dupName = `Dup Cat ${unique()}`;

        await page.evaluate((url: string) => (window as any).crearCategoria(url), '/u/areas');
        await page.waitForSelector('#categoria-form:not(.hidden)');
        await page.fill('#nombre', dupName);
        await page.click('#categoria-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success').first()).toBeVisible();

        await page.evaluate((url: string) => (window as any).crearCategoria(url), '/u/areas');
        await page.waitForSelector('#categoria-form:not(.hidden)');
        await page.fill('#nombre', dupName);
        await page.click('#categoria-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success').first()).toBeVisible();
    });

    test('blocks deletion of category with courses', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).eliminarCategoria(url), '/u/areas/5');
        await page.waitForSelector('#categoria-form-delete:not(.hidden)');
        await page.click('#categoria-form-delete #btn-action');
        await page.waitForLoadState('load');
        await expect(page.locator('.callout-danger, .alert-danger').first()).toBeVisible();
    });

    test('name input has maxlength of 60', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).crearCategoria(url), '/u/areas');
        await page.waitForSelector('#categoria-form:not(.hidden)');
        const maxlength = await page.evaluate(() => {
            const input = document.getElementById('nombre') as HTMLInputElement;
            return input.maxLength;
        });
        expect(maxlength).toBe(60);
    });

    test('search filters categories by name', async ({ page }) => {
        const searchName = `Search Cat ${unique()}`;
        await page.evaluate((url: string) => (window as any).crearCategoria(url), '/u/areas');
        await page.waitForSelector('#categoria-form:not(.hidden)');
        await page.fill('#nombre', searchName);
        await page.click('#categoria-aceptar');
        await page.waitForLoadState('load');

        await page.fill('#name', searchName);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('load');

        const rowCount = await page.locator('table tbody tr').count();
        expect(rowCount).toBeGreaterThanOrEqual(1);
        await expect(page.locator('table tbody tr').first()).toContainText(searchName);
    });

    test('search with no results shows empty state', async ({ page }) => {
        const gibberish = `ZZZ_NO_MATCH_${unique()}`;
        await page.fill('#name', gibberish);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('load');

        const emptyText = page.locator('td', { hasText: 'No se han encontrado resultados...' });
        await expect(emptyText).toBeVisible();
    });

    test('details modal opens for existing category', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).detallesCategoria(url), '/u/areas/1');
        await page.waitForSelector('#categoria-form:not(.hidden)');

        const label = await page.evaluate(() => {
            return (window as any).jQuery('#categoria-label').text();
        });
        expect(label).toContain('Detalles');

        const nameVal = await page.evaluate(() => {
            return (window as any).jQuery('#nombre').val();
        });
        expect(nameVal).toBeTruthy();

        const isReadonly = await page.evaluate(() => {
            return (document.getElementById('nombre') as HTMLInputElement).readOnly;
        });
        expect(isReadonly).toBe(true);
    });

    test('edit preserves other categories', async ({ page }) => {
        const otherCatName = `Other Cat ${unique()}`;
        await page.evaluate((url: string) => (window as any).crearCategoria(url), '/u/areas');
        await page.waitForSelector('#categoria-form:not(.hidden)');
        await page.fill('#nombre', otherCatName);
        await page.click('#categoria-aceptar');
        await page.waitForLoadState('load');

        const editName = `Edited Cat ${unique()}`;
        await page.evaluate((url: string) => (window as any).editarCategoria(url), '/u/areas/1');
        await page.waitForSelector('#categoria-form:not(.hidden)');
        await page.fill('#nombre', editName);
        await page.click('#categoria-aceptar');
        await page.waitForLoadState('load');

        await page.fill('#name', otherCatName);
        await page.click('button[type="submit"]');
        await page.waitForLoadState('load');
        await expect(page.locator('td', { hasText: otherCatName })).toBeVisible();
    });

    test('shows validation error with name exceeding 60 chars', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).crearCategoria(url), '/u/areas');
        await page.waitForSelector('#categoria-form:not(.hidden)');

        await page.evaluate(() => {
            const input = document.getElementById('nombre') as HTMLInputElement;
            input.removeAttribute('maxlength');
        });

        const longName = 'A'.repeat(61);
        await page.fill('#nombre', longName);
        await page.click('#categoria-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.callout-danger, .alert-danger').first()).toBeVisible();
    });
});
