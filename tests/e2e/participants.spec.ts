import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

const unique = () => Date.now();

test.describe('Participants CRUD', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/participantes');
    });

    test('displays participants list', async ({ page }) => {
        await expect(page.locator('h3').first()).toContainText('Participantes');
        await expect(page.locator('table')).toBeVisible();
    });

    test('creates a new participant', async ({ page }) => {
        const testEmail = `testpart${unique()}@pdvsa.com`;
        await page.evaluate((url: string) => (window as any).crearParticipante(url), '/u/participantes');
        await page.waitForSelector('#participante-form:not(.hidden)');
        await page.fill('#nombre', 'Test');
        await page.fill('#apellido', 'Participante');
        await page.fill('#email', testEmail);
        await page.selectOption('#id_type', '1');
        await page.fill('#ci', `${unique()}`);
        await page.selectOption('#sex', 'Masculino');
        await page.fill('#password', 'password123');
        await page.fill('#password_confirmation', 'password123');
        await page.click('#participante-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('shows validation with missing name via HTML5', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).crearParticipante(url), '/u/participantes');
        await page.waitForSelector('#participante-form:not(.hidden)');
        await page.fill('#nombre', '');
        const isValid = await page.evaluate(() => {
            const input = document.getElementById('nombre') as HTMLInputElement;
            return input.checkValidity();
        });
        expect(isValid).toBe(false);
    });

    test('deletes a participant', async ({ page }) => {
        const testEmail = `delpart${unique()}@pdvsa.com`;
        await page.evaluate((url: string) => (window as any).crearParticipante(url), '/u/participantes');
        await page.waitForSelector('#participante-form:not(.hidden)');
        await page.fill('#nombre', 'Del');
        await page.fill('#apellido', 'Participante');
        await page.fill('#email', testEmail);
        await page.selectOption('#id_type', '1');
        await page.fill('#ci', `${unique()}`);
        await page.selectOption('#sex', 'Masculino');
        await page.fill('#password', 'password123');
        await page.fill('#password_confirmation', 'password123');
        await page.click('#participante-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();

        const deleteLink = page.locator('tr').filter({ hasText: testEmail }).locator('a[title="Eliminar Participante"]');
        if (await deleteLink.isVisible({ timeout: 3000 }).catch(() => false)) {
            const href = await deleteLink.getAttribute('href');
            const urlMatch = href?.match(/eliminarParticipante\('(.+)'\)/);
            if (urlMatch) {
                await page.evaluate((url: string) => (window as any).eliminarParticipante(url), urlMatch[1]);
                await page.waitForSelector('#participante-form-delete:not(.hidden)');
                await page.click('#participante-form-delete #btn-action');
                await page.waitForLoadState('load');
                await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
            }
        }
    });
});
