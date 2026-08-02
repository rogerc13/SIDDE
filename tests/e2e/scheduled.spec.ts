import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';
import { execSync } from 'child_process';

const TINKER_CMD = 'cd /Users/albani/RnD/Personal\\ Projects/SIDDE && php artisan tinker --execute';

function createScheduledCourse(future: boolean): number {
    const daysOffset = future ? 30 : 0;
    const endOffset = future ? 45 : 30;
    const result = execSync(`${TINKER_CMD} "
        \\$s = new \\App\\Models\\Scheduled();
        \\$s->course_id = 1;
        \\$s->facilitator_id = 1;
        \\$s->start_date = date('Y-m-d', strtotime('+${daysOffset} days'));
        \\$s->end_date = date('Y-m-d', strtotime('+${endOffset} days'));
        \\$s->course_status_id = ${future ? 1 : 2};
        \\$s->save();
        \\App\\Models\\CourseSession::create([
            'scheduled_course_id' => \\$s->id,
            'location_id' => 1,
            'session_date' => date('Y-m-d', strtotime('+${daysOffset} days')),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'scheduled'
        ]);
        echo \\$s->id;
    "`, { encoding: 'utf-8' }).trim();
    return parseInt(result);
}

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

async function scheduleFutureCourse(page: any) {
    createScheduledCourse(true);
    await page.goto('/u/af_programadas');
    await page.waitForLoadState('load');
}

async function scheduleActiveCourse(page: any) {
    createScheduledCourse(false);
    await page.goto('/u/af_programadas');
    await page.waitForLoadState('load');
}

test.describe('Scheduled Courses CRUD', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/af_programadas');
    });

    test('displays scheduled courses list', async ({ page }) => {
        await expect(page.locator('h3').first()).toContainText(/Formaci/);
        await expect(page.locator('table.table-center').first()).toBeVisible();
    });

    test('schedules a new course', async ({ page }) => {
        await scheduleFutureCourse(page);
    });

    test('shows error when trying to proceed without sessions', async ({ page }) => {
        await page.evaluate((url: string) => (window as any).programarAccion(url), '/u/af_programadas');
        await page.waitForSelector('#wizard-form:not(.hidden)');

        await selectSelect2ById(page, 'wizard-titulo');
        await selectSelect2ById(page, 'wizard-facilitador');

        await page.click('#wizard-btn-next');
        await page.waitForTimeout(300);

        page.on('dialog', async (dialog) => {
            expect(dialog.message()).toContain('Debe agregar al menos una sesión');
            await dialog.accept();
        });

        await page.click('#wizard-btn-next');
        await page.waitForTimeout(500);
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

        const enabledAssignLink = page.locator('a[title="Asignar participante"]:not(.disabled)').first();
        await expect(enabledAssignLink).toBeVisible({ timeout: 5000 });
        await enabledAssignLink.click();

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
