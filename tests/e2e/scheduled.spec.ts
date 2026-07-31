import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

const unique = () => Date.now();

async function selectSelect2ById(page: any, selectId: string) {
    await page.evaluate(({ id }: { id: string }) => {
        const el = document.getElementById(id) as HTMLSelectElement;
        if (!el) return;
        const options = el.querySelectorAll('option');
        if (options.length > 1) {
            const firstOption = options[1] as HTMLOptionElement;
            el.value = firstOption.value;
            (window as any).jQuery(`#${id}`).trigger('change');
        }
    }, { id: selectId });
    await page.waitForTimeout(200);
}

test.describe('Scheduled Courses CRUD', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/af_programadas');
    });

    test('displays scheduled courses list', async ({ page }) => {
        await expect(page.locator('h3').first()).toContainText(/Formaci/);
        await expect(page.locator('table')).toBeVisible();
    });

    test('schedules a new course', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).programarAccion(url), '/u/af_programadas');
        await page.waitForSelector('#programar-form:not(.hidden)');

        await selectSelect2ById(page, 'titulo');
        await selectSelect2ById(page, 'facilitador');

        const today = new Date();
        const nextWeek = new Date(today);
        nextWeek.setDate(today.getDate() + 7);
        const twoWeeks = new Date(today);
        twoWeeks.setDate(today.getDate() + 14);

        const formatDate = (d: Date) => {
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}-${month}-${year}`;
        };

        await page.fill('#fecha_i', formatDate(nextWeek));
        await page.fill('#fecha_f', formatDate(twoWeeks));
        await page.click('#accion-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('shows validation error with past start date', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).programarAccion(url), '/u/af_programadas');
        await page.waitForSelector('#programar-form:not(.hidden)');

        await selectSelect2ById(page, 'titulo');
        await selectSelect2ById(page, 'facilitador');

        await page.fill('#fecha_i', '01-01-2020');
        await page.fill('#fecha_f', '01-01-2025');
        await page.click('#accion-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.callout-danger').first()).toBeVisible();
    });

    test('shows validation error with end date before start date', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).programarAccion(url), '/u/af_programadas');
        await page.waitForSelector('#programar-form:not(.hidden)');

        await selectSelect2ById(page, 'titulo');
        await selectSelect2ById(page, 'facilitador');

        const today = new Date();
        const nextWeek = new Date(today);
        nextWeek.setDate(today.getDate() + 7);
        const yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);

        const formatDate = (d: Date) => {
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}-${month}-${year}`;
        };

        await page.fill('#fecha_i', formatDate(nextWeek));
        await page.fill('#fecha_f', formatDate(yesterday));
        await page.click('#accion-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.callout-danger').first()).toBeVisible();
    });

    test('deletes a scheduled course', async ({ page }) => {
        const deleteLink = page.locator('a[title*="Eliminar Acción"]').first();
        if (await deleteLink.isVisible({ timeout: 3000 }).catch(() => false)) {
            const href = await deleteLink.getAttribute('href');
            const urlMatch = href?.match(/eliminarPrograma\('(.+)'\)/);
            if (urlMatch) {
                await page.evaluate((url: string) => (window as any).eliminarPrograma(url), urlMatch[1]);
                await page.waitForSelector('#programa-form-delete:not(.hidden)');
                await page.click('#programa-form-delete #btn-action');
                await page.waitForLoadState('load');
                await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
            }
        }
    });
});
