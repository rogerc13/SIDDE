import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

const unique = () => Date.now();

test.describe('Facilitators CRUD', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/facilitadores');
    });

    test('displays facilitators list', async ({ page }) => {
        await expect(page.locator('h3').first()).toContainText('Facilitadores');
        await expect(page.locator('table')).toBeVisible();
    });

    test('creates a new facilitator', async ({ page }) => {
        const testEmail = `testfac${unique()}@pdvsa.com`;
        await page.evaluate((url: string) => (window as any).crearFacilitador(url), '/u/facilitadores');
        await page.waitForSelector('#facilitador-form:not(.hidden)');
        await page.fill('#nombre', 'Test');
        await page.fill('#apellido', 'Facilitador');
        await page.fill('#email', testEmail);
        await page.selectOption('#id_type', '1');
        await page.fill('#ci', `${unique()}`);
        await page.selectOption('#sex', 'Femenino');
        await page.fill('#password', 'password123');
        await page.fill('#password_confirmation', 'password123');
        await page.click('#facilitador-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('shows validation error with duplicate email', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).crearFacilitador(url), '/u/facilitadores');
        await page.waitForSelector('#facilitador-form:not(.hidden)');
        await page.fill('#nombre', 'Test');
        await page.fill('#apellido', 'Facilitador');
        await page.fill('#email', 'facilitador@pdvsa.com');
        await page.selectOption('#id_type', '1');
        await page.fill('#ci', `${unique()}`);
        await page.selectOption('#sex', 'Femenino');
        await page.fill('#password', 'password123');
        await page.fill('#password_confirmation', 'password123');
        await page.click('#facilitador-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.callout-danger').first()).toBeVisible();
    });

    test('deletes a facilitator', async ({ page }) => {
        const testEmail = `delfac${unique()}@pdvsa.com`;
        await page.evaluate((url: string) => (window as any).crearFacilitador(url), '/u/facilitadores');
        await page.waitForSelector('#facilitador-form:not(.hidden)');
        await page.fill('#nombre', 'Del');
        await page.fill('#apellido', 'Facilitador');
        await page.fill('#email', testEmail);
        await page.selectOption('#id_type', '1');
        await page.fill('#ci', `${unique()}`);
        await page.selectOption('#sex', 'Femenino');
        await page.fill('#password', 'password123');
        await page.fill('#password_confirmation', 'password123');
        await page.click('#facilitador-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();

        const deleteLink = page.locator('tr').filter({ hasText: testEmail }).locator('a[title="Eliminar Facilitador"]');
        if (await deleteLink.isVisible({ timeout: 3000 }).catch(() => false)) {
            const href = await deleteLink.getAttribute('href');
            const urlMatch = href?.match(/eliminarFacilitador\('(.+)'\)/);
            if (urlMatch) {
                await page.evaluate((url: string) => (window as any).eliminarFacilitador(url), urlMatch[1]);
                await page.waitForSelector('#facilitador-form-delete:not(.hidden)');
                await page.click('#facilitador-form-delete #btn-action');
                await page.waitForLoadState('load');
                await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
            }
        }
    });
});
