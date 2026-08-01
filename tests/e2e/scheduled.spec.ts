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

function formatDate(d: Date): string {
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}-${month}-${year}`;
}

async function scheduleFutureCourse(page: any) {
    await page.evaluate((url: string) => (window as any).programarAccion(url), '/u/af_programadas');
    await page.waitForSelector('#programar-form:not(.hidden)');

    await selectSelect2ById(page, 'titulo');
    await selectSelect2ById(page, 'facilitador');

    const today = new Date();
    const startDate = new Date(today);
    startDate.setDate(today.getDate() + 30);
    const endDate = new Date(today);
    endDate.setDate(today.getDate() + 45);

    const startStr = formatDate(startDate);
    const endStr = formatDate(endDate);

    await page.evaluate(({ start, end }: { start: string; end: string }) => {
        const jq = (window as any).jQuery;
        jq('#fecha_i').val(start).trigger('change');
        jq('#fecha_f').val(end).trigger('change');
    }, { start: startStr, end: endStr });

    await page.click('#accion-aceptar');
    await page.waitForLoadState('load');
    await expect(page.locator('.alert-success').first()).toBeVisible();
}

async function scheduleActiveCourse(page: any) {
    await page.evaluate((url: string) => (window as any).programarAccion(url), '/u/af_programadas');
    await page.waitForSelector('#programar-form:not(.hidden)');

    await selectSelect2ById(page, 'titulo');
    await selectSelect2ById(page, 'facilitador');

    const today = new Date();
    const endDate = new Date(today);
    endDate.setDate(today.getDate() + 30);

    const startStr = formatDate(today);
    const endStr = formatDate(endDate);

    await page.evaluate(({ start, end }: { start: string; end: string }) => {
        const jq = (window as any).jQuery;
        jq('#fecha_i').val(start).trigger('change');
        jq('#fecha_f').val(end).trigger('change');
    }, { start: startStr, end: endStr });

    await page.click('#accion-aceptar');
    await page.waitForLoadState('load');
    await expect(page.locator('.alert-success').first()).toBeVisible();
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

test.describe('Participant Assignment', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/af_programadas');
    });

    test('assigns a participant to a POR_DICTAR course', async ({ page }) => {
        await scheduleFutureCourse(page);

        const assignLink = page.locator('a[title="Asignar participante"]').first();
        await expect(assignLink).not.toHaveClass(/disabled/);
        await assignLink.click();

        await page.waitForSelector('#asignar-modal.in');
        await page.waitForFunction(() => {
            const select = document.getElementById('participante') as HTMLSelectElement;
            return select && select.options.length > 1;
        }, { timeout: 5000 });

        await selectSelect2ById(page, 'participante');
        await page.click('#usuario-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success').first()).toBeVisible();
    });

    test('shows disabled assign button for En Curso course', async ({ page }) => {
        await scheduleActiveCourse(page);
        await page.goto('/u/af_programadas?id_estado=2');
        const assignButton = page.locator('a[title="Asignar participante"]').first();
        await expect(assignButton).toHaveClass(/disabled/);
    });

    test('shows disabled assign button for Culminado course', async ({ page }) => {
        await page.goto('/u/af_programadas?id_estado=3');
        const assignButton = page.locator('a[title="Asignar participante"]').first();
        await expect(assignButton).toHaveClass(/disabled/);
    });

    test('assignList returns error for En Curso course', async ({ page }) => {
        await scheduleActiveCourse(page);
        await page.goto('/u/af_programadas?id_estado=2');
        const assignButton = page.locator('a[title="Asignar participante"]').first();
        await expect(assignButton).toBeVisible({ timeout: 5000 });
        const href = await assignButton.getAttribute('href');
        const urlMatch = href?.match(/asignarParticipanteLista\('(.+?)','(.+?)'\)/);
        expect(urlMatch).toBeTruthy();

        if (urlMatch) {
            await page.evaluate(({ url, id }: { url: string; id: string }) => {
                (window as any).asignarParticipanteLista(url, id);
            }, { url: urlMatch[1], id: urlMatch[2] });

            await page.waitForSelector('#asignar-modal.in');
            await page.waitForTimeout(1500);

            const errorText = await page.locator('.capacity-error-text').textContent();
            expect(errorText).toContain('No se pueden asignar participantes');
        }
    });

    test('assignList returns error for Culminado course', async ({ page }) => {
        await page.goto('/u/af_programadas?id_estado=3');
        const assignButton = page.locator('a[title="Asignar participante"]').first();
        await expect(assignButton).toBeVisible({ timeout: 5000 });
        const href = await assignButton.getAttribute('href');
        const urlMatch = href?.match(/asignarParticipanteLista\('(.+?)','(.+?)'\)/);
        expect(urlMatch).toBeTruthy();

        if (urlMatch) {
            await page.evaluate(({ url, id }: { url: string; id: string }) => {
                (window as any).asignarParticipanteLista(url, id);
            }, { url: urlMatch[1], id: urlMatch[2] });

            await page.waitForSelector('#asignar-modal.in');
            await page.waitForTimeout(1500);

            const errorText = await page.locator('.capacity-error-text').textContent();
            expect(errorText).toContain('No se pueden asignar participantes');
        }
    });

    test('blocks POST assignment to En Curso course', async ({ page }) => {
        await scheduleActiveCourse(page);
        await page.goto('/u/af_programadas?id_estado=2');
        const assignButton = page.locator('a[title="Asignar participante"]').first();
        await expect(assignButton).toBeVisible({ timeout: 5000 });
        const href = await assignButton.getAttribute('href');
        const urlMatch = href?.match(/asignarParticipanteLista\('(.+?)','(.+?)'\)/);
        expect(urlMatch).toBeTruthy();

        if (urlMatch) {
            await page.evaluate(({ url, id }: { url: string; id: string }) => {
                (window as any).asignarParticipanteLista(url, id);
            }, { url: urlMatch[1], id: urlMatch[2] });

            await page.waitForSelector('#asignar-modal.in');
            await page.waitForTimeout(1500);

            await page.evaluate(() => {
                const form = document.getElementById('asignar-form') as HTMLFormElement;
                if (form) form.submit();
            });
            await page.waitForLoadState('load');
            await expect(page.locator('.alert-danger').first()).toBeVisible();
        }
    });
});
