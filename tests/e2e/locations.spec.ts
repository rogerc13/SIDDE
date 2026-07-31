import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

const unique = () => Date.now();

test.describe('Locations CRUD', () => {
    let testName = '';

    test.beforeEach(async ({ page }) => {
        testName = `Test Loc ${unique()}`;
        await loginAs(page, 'admin');
        await page.goto('/u/ubicaciones');
    });

    test('displays locations list', async ({ page }) => {
        await expect(page.locator('h3').first()).toContainText('Ubicaciones');
        await expect(page.locator('table')).toBeVisible();
    });

    test('creates a new location', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).crearLocation(url), '/u/ubicaciones');
        await page.waitForSelector('#location-form:not(.hidden)');
        await page.fill('#nombre', testName);
        await page.click('#location-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('shows validation with empty name via HTML5', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).crearLocation(url), '/u/ubicaciones');
        await page.waitForSelector('#location-form:not(.hidden)');
        await page.fill('#nombre', '');
        const isValid = await page.evaluate(() => {
            const input = document.getElementById('nombre') as HTMLInputElement;
            return input.checkValidity();
        });
        expect(isValid).toBe(false);
    });

    test('shows validation error with duplicate name', async ({ page }) => {
        const uniqueName = `DupLoc ${unique()}`;
        await page.evaluate((url: string) => (window as any).crearLocation(url), '/u/ubicaciones');
        await page.waitForSelector('#location-form:not(.hidden)');
        await page.fill('#nombre', uniqueName);
        await page.click('#location-aceptar');
        await page.waitForLoadState('load');

        await page.evaluate((url: string) => (window as any).crearLocation(url), '/u/ubicaciones');
        await page.waitForSelector('#location-form:not(.hidden)');
        await page.fill('#nombre', uniqueName);
        await page.click('#location-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.callout-danger').first()).toBeVisible();
    });

    test('edits an existing location', async ({ page }) => {
        const editName = `Edited Loc ${unique()}`;
        await page.evaluate((url: string) => (window as any).editarLocation(url), '/u/ubicaciones/1');
        await page.waitForSelector('#location-form:not(.hidden)');
        await page.fill('#nombre', editName);
        await page.click('#location-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('deletes a location', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).crearLocation(url), '/u/ubicaciones');
        await page.waitForSelector('#location-form:not(.hidden)');
        const delName = `Del Loc ${unique()}`;
        await page.fill('#nombre', delName);
        await page.click('#location-aceptar');
        await page.waitForLoadState('load');

        const deleteLink = page.locator('a[title="Eliminar ubicación"]').first();
        const href = await deleteLink.getAttribute('href');
        const urlMatch = href?.match(/eliminarLocation\('(.+)'\)/);
        if (urlMatch) {
            await page.evaluate((url: string) => (window as any).eliminarLocation(url), urlMatch[1]);
            await page.waitForSelector('#location-form-delete:not(.hidden)');
            await page.click('#location-form-delete #btn-action');
            await page.waitForLoadState('load');
            await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
        }
    });
});
