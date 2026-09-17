import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';
import { execSync } from 'child_process';

const TINKER_CMD = 'php artisan tinker --execute';

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

function createScheduledWithStatus(statusId: number, daysOffset = 30): number {
    const result = execSync(`${TINKER_CMD} "
        \\$s = new \\App\\Models\\Scheduled();
        \\$s->course_id = 1;
        \\$s->facilitator_id = 1;
        \\$s->start_date = date('Y-m-d', strtotime('+${daysOffset} days'));
        \\$s->end_date = date('Y-m-d', strtotime('+${daysOffset + 15} days'));
        \\$s->course_status_id = ${statusId};
        \\$s->save();
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

function seedWizardData(): void {
    execSync(`${TINKER_CMD} "
        \\$cat = \\App\\Models\\Category::create(['name' => 'WizardTestCat_' . uniqid()]);
        \\$mod = \\App\\Models\\Modality::create(['name' => 'WizardTestMod_' . uniqid()]);
        \\$course = \\App\\Models\\Course::create([
            'code' => 'WZ-' . rand(1000, 9999),
            'title' => 'Wizard Test Course',
            'category_id' => \\$cat->id,
            'modality_id' => \\$mod->id,
            'objective' => 'test',
            'duration' => 8,
            'addressed' => 'test'
        ]);
        \\$capacity = \\App\\Models\\Capacity::create(['course_id' => \\$course->id, 'min' => 1, 'max' => 30]);
        \\$person = \\App\\Models\\Person::create([
            'name' => 'Wizard',
            'last_name' => 'Test',
            'id_number' => rand(1000000, 9999999),
            'id_type_id' => 1
        ]);
        \\$fac = \\App\\Models\\Facilitator::create(['person_id' => \\$person->id]);
        \\App\\Models\\User::create([
            'role_id' => 4,
            'person_id' => \\$person->id,
            'email' => 'wizard_' . rand(1000, 9999) . '@test.com',
            'password' => bcrypt('123456')
        ]);
        \\App\\Models\\Location::create(['name' => 'WizardTestLocation_' . uniqid()]);
        echo \\$course->id;
    "`, { encoding: 'utf-8' });
}

async function selectOptionByText(page: any, selectId: string, text: string) {
    await page.evaluate(({ id, text }: { id: string; text: string }) => {
        const el = document.getElementById(id) as HTMLSelectElement;
        if (!el) return;
        const matches = Array.from(el.querySelectorAll('option')).filter((o) => o.textContent?.includes(text)) as HTMLOptionElement[];
        const option = matches[matches.length - 1];
        if (option) {
            el.value = option.value;
            (window as any).jQuery(`#${id}`).trigger('change');
        }
    }, { id: selectId, text });
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
        await page.goto('/u/af_programadas/crear');
        await page.waitForSelector('#wizard-form');

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

    test('schedules a new course through the wizard UI with 12-hour times', async ({ page }) => {
        seedWizardData();
        await page.goto('/u/af_programadas');
        await page.waitForLoadState('load');

        page.on('dialog', async (dialog) => {
            await dialog.accept();
        });

        await page.goto('/u/af_programadas/crear');
        await page.waitForSelector('#wizard-form');

        await selectOptionByText(page, 'wizard-titulo', 'Wizard Test Course');
        await selectOptionByText(page, 'wizard-facilitador', 'Wizard Test');

        await page.click('#wizard-btn-next');
        await page.waitForSelector('#panel-step2.active');

        await page.click('button[onclick^="openNewSessionModal"]');
        await page.waitForSelector('#add-session-modal:not(.fade):not(.hidden), #add-session-modal.in');

        await selectOptionByText(page, 'add-session-location', 'WizardTestLocation');

        await page.evaluate(() => {
            (window as any).jQuery('#add-session-date').val('14-08-2026');
            (window as any).jQuery('#add-session-start').val('8:00 AM');
            (window as any).jQuery('#add-session-end').val('10:00 AM');
        });
        await page.waitForTimeout(200);

        await page.click('#add-session-modal .modal-footer button.btn-primary');
        await page.waitForTimeout(500);

        const row1 = page.locator('#wizard-sessions-tbody tr', { hasText: '8:00 AM' });
        await expect(row1).toBeVisible();
        await expect(row1).toContainText('10:00 AM');
        await expect(row1).toContainText('2 hrs');
        await expect(row1).toContainText('14-08-2026');

        await page.click('button[onclick^="openNewSessionModal"]');
        await page.waitForSelector('#add-session-modal:not(.fade):not(.hidden), #add-session-modal.in');
        await selectOptionByText(page, 'add-session-location', 'WizardTestLocation');
        await page.evaluate(() => {
            (window as any).jQuery('#add-session-date').val('14-08-2026');
            (window as any).jQuery('#add-session-start').val('10:00 AM');
            (window as any).jQuery('#add-session-end').val('12:00 PM');
        });
        await page.waitForTimeout(200);
        await page.click('#add-session-modal .modal-footer button.btn-primary');
        await page.waitForTimeout(500);

        await page.click('button[onclick^="openNewSessionModal"]');
        await page.waitForSelector('#add-session-modal:not(.fade):not(.hidden), #add-session-modal.in');
        await selectOptionByText(page, 'add-session-location', 'WizardTestLocation');
        await page.evaluate(() => {
            (window as any).jQuery('#add-session-date').val('15-08-2026');
            (window as any).jQuery('#add-session-start').val('1:00 PM');
            (window as any).jQuery('#add-session-end').val('3:00 PM');
        });
        await page.waitForTimeout(200);
        await page.click('#add-session-modal .modal-footer button.btn-primary');
        await page.waitForTimeout(500);

        await page.click('button[onclick^="openNewSessionModal"]');
        await page.waitForSelector('#add-session-modal:not(.fade):not(.hidden), #add-session-modal.in');
        await selectOptionByText(page, 'add-session-location', 'WizardTestLocation');
        await page.evaluate(() => {
            (window as any).jQuery('#add-session-date').val('15-08-2026');
            (window as any).jQuery('#add-session-start').val('3:00 PM');
            (window as any).jQuery('#add-session-end').val('5:00 PM');
        });
        await page.waitForTimeout(200);
        await page.click('#add-session-modal .modal-footer button.btn-primary');
        await page.waitForTimeout(500);

        const allRows = page.locator('#wizard-sessions-tbody tr');
        await expect(allRows).toHaveCount(4);

        await page.click('#wizard-btn-next');
        await page.waitForSelector('#panel-step3.active');

        await expect(page.locator('#review-sessions-tbody')).toContainText('WizardTestLocation');
        await expect(page.locator('#review-sessions-tbody')).toContainText('14-08-2026');
        await expect(page.locator('#review-total-hours')).toContainText('8 / 8 horas');

        await page.click('#wizard-btn-submit');
        await page.waitForSelector('#wizard-modal', { state: 'hidden' });
        await page.waitForLoadState('load');

        await expect(page.locator('.alert-success').first()).toContainText('Acción de formación programada exitosamente');

        await page.fill('#titulos', 'Wizard Test Course');
        await page.click('form[action*="af_programadas"] button[type="submit"]');
        await page.waitForLoadState('load');

        const scheduledRow = page.locator('table.table-center tbody tr', { hasText: 'Wizard Test Course' }).first();
        await expect(scheduledRow).toBeVisible({ timeout: 5000 });
    });

    test('deletes a future scheduled course', async ({ page }) => {
        await scheduleFutureCourse(page);

        const row = page.locator('.af-index table tbody tr').filter({ hasText: 'Por Dictar' }).first();
        await expect(row).toBeVisible();
        await row.locator('a[title="Más funciones"]').click();

        const deleteItem = row.locator('.dropdown-menu li:not(.disabled) a', { hasText: 'Eliminar' });
        await expect(deleteItem).toBeVisible();
        await deleteItem.click();

        await page.waitForSelector('#programa-form-delete:not(.hidden)');
        await page.click('#programa-form-delete #btn-action');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success').first()).toBeVisible();
    });

    test('opens the row actions dropdown from the index', async ({ page }) => {
        await scheduleFutureCourse(page);

        const row = page.locator('.af-index table tbody tr').filter({ hasText: 'Por Dictar' }).first();
        await expect(row).toBeVisible();
        await row.locator('a[title="Más funciones"]').click();

        const menu = row.locator('.dropdown-menu');
        await expect(menu).toBeVisible();
        await expect(menu).toContainText('Lista de participantes');
        await expect(menu).toContainText('Editar programación');
        await expect(menu).toContainText('Cancelar');

        await menu.locator('a', { hasText: 'Editar programación' }).click();
        await page.waitForLoadState('load');
        await expect(page).toHaveURL(/\/editar$/);
    });

    test('opens the details page with header actions and cancel modal', async ({ page }) => {
        await scheduleFutureCourse(page);

        const row = page.locator('.af-index table tbody tr').filter({ hasText: 'Por Dictar' }).first();
        await expect(row).toBeVisible();
        await row.locator('a[title="Ver detalles"]').click();
        await page.waitForLoadState('load');

        await expect(page.locator('.details-header')).toBeVisible();
        await expect(page.locator('.details-meta')).toContainText('Creada:');
        await expect(page.locator('.details-actions')).toContainText('Más funciones');
        await expect(page.locator('.details-actions')).toContainText('Asignar participante');

        await page.locator('.details-actions .dropdown-toggle').click();
        await page.locator('.details-actions .dropdown-menu a', { hasText: 'Cancelar' }).click();
        await expect(page.locator('#programa-modal-cancel')).toHaveClass(/in/);
    });

    test('switches between details tabs', async ({ page }) => {
        await scheduleFutureCourse(page);

        const row = page.locator('.af-index table tbody tr').filter({ hasText: 'Por Dictar' }).first();
        await expect(row).toBeVisible();
        await row.locator('a[title="Ver detalles"]').click();
        await page.waitForLoadState('load');

        await expect(page.locator('#tab-programacion')).toBeVisible();
        await expect(page.locator('#details-calendar')).toBeVisible();

        await page.click('a[href="#tab-ficha"]');
        await expect(page.locator('#tab-ficha')).toBeVisible();
        await expect(page.locator('#tab-programacion')).toBeHidden();

        await page.click('a[href="#tab-documentos"]');
        await expect(page.locator('#tab-documentos')).toBeVisible();

        await page.click('a[href="#tab-participantes"]');
        await expect(page.locator('#tab-participantes')).toBeVisible();
    });
});

test.describe('Participant Assignment', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/af_programadas');
    });

    test('assigns a participant to a POR_DICTAR course', async ({ page }) => {
        await scheduleFutureCourse(page);

        const row = page.locator('.af-index table tbody tr').filter({ hasText: 'Por Dictar' }).first();
        await expect(row).toBeVisible({ timeout: 5000 });
        await row.locator('a[title="Más funciones"]').click();

        const assignItem = row.locator('.dropdown-menu li:not(.disabled) a', { hasText: 'Asignar participante' });
        await expect(assignItem).toBeVisible();
        await assignItem.click();

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

    test('shows disabled assign item for En Curso course', async ({ page }) => {
        await scheduleActiveCourse(page);
        await page.goto('/u/af_programadas?id_estado=2');
        const row = page.locator('.af-index table tbody tr').first();
        await expect(row).toBeVisible({ timeout: 5000 });
        await row.locator('a[title="Más funciones"]').click();
        await expect(row.locator('.dropdown-menu li', { hasText: 'Asignar participante' })).toHaveClass(/disabled/);
    });

    test('shows disabled assign item for Culminado course', async ({ page }) => {
        await page.goto('/u/af_programadas?id_estado=3');
        const row = page.locator('.af-index table tbody tr').first();
        await expect(row).toBeVisible({ timeout: 5000 });
        await row.locator('a[title="Más funciones"]').click();
        await expect(row.locator('.dropdown-menu li', { hasText: 'Asignar participante' })).toHaveClass(/disabled/);
    });

    test('assignList returns error for En Curso course', async ({ page }) => {
        await scheduleActiveCourse(page);
        await page.goto('/u/af_programadas?id_estado=2');
        const assignLink = page.locator('.af-index a[href*="asignarParticipanteLista"]').first();
        await expect(assignLink).toBeAttached({ timeout: 5000 });
        const href = await assignLink.getAttribute('href');
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
        const assignLink = page.locator('.af-index a[href*="asignarParticipanteLista"]').first();
        await expect(assignLink).toBeAttached({ timeout: 5000 });
        const href = await assignLink.getAttribute('href');
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
        const assignLink = page.locator('.af-index a[href*="asignarParticipanteLista"]').first();
        await expect(assignLink).toBeAttached({ timeout: 5000 });
        const href = await assignLink.getAttribute('href');
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

test.describe('Unsaved Changes Guard', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/af_programadas/crear');
        await page.waitForSelector('#wizard-form');
    });

    async function reachStepTwo(page: any) {
        await selectSelect2ById(page, 'wizard-titulo');
        await selectSelect2ById(page, 'wizard-facilitador');
        await page.click('#wizard-btn-next');
        await page.waitForSelector('#panel-step2.active');
    }

    test('warns before leaving create wizard via sidebar link', async ({ page }) => {
        await reachStepTwo(page);

        await page.click('#main-menu a[href$="/u/af_programadas"]');
        await expect(page.locator('#leave-warning-modal')).toBeVisible();

        await page.getByRole('button', { name: 'Quedarse' }).click();
        await expect(page.locator('#leave-warning-modal')).toBeHidden();
        await expect(page).toHaveURL(/af_programadas\/crear/);
    });

    test('leaves when confirming unsaved changes', async ({ page }) => {
        await reachStepTwo(page);

        await page.click('#main-menu a[href$="/u/af_programadas"]');
        await expect(page.locator('#leave-warning-modal')).toBeVisible();

        await page.click('#leave-warning-confirm');
        await page.waitForLoadState('load');
        await expect(page).toHaveURL(/\/u\/af_programadas$/);
    });

    test('shows native dialog on reload when there are changes', async ({ page }) => {
        await reachStepTwo(page);

        let dialogSeen = false;
        page.on('dialog', async (dialog) => {
            dialogSeen = true;
            await dialog.accept();
        });
        await page.reload();
        await page.waitForLoadState('load');
        expect(dialogSeen).toBe(true);
    });
});

async function openWizardStepTwo(page: any) {
    seedWizardData();
    await page.goto('/u/af_programadas/crear');
    await page.waitForSelector('#wizard-form');
    await selectOptionByText(page, 'wizard-titulo', 'Wizard Test Course');
    await selectOptionByText(page, 'wizard-facilitador', 'Wizard Test');
    await page.click('#wizard-btn-next');
    await page.waitForSelector('#panel-step2.active');
}

async function fillSessionModal(page: any, locationText: string, displayDate: string, start12: string, end12: string) {
    await page.click('button[onclick^="openNewSessionModal"]');
    await page.waitForSelector('#add-session-modal.in');
    await selectOptionByText(page, 'add-session-location', locationText);
    await page.evaluate(({ d, s, e }: any) => {
        (window as any).jQuery('#add-session-date').val(d);
        (window as any).jQuery('#add-session-start').val(s);
        (window as any).jQuery('#add-session-end').val(e);
    }, { d: displayDate, s: start12, e: end12 });
    await page.waitForTimeout(150);
}

test.describe('Session Modal', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
    });

    test('blocks a conflicting session at the same location', async ({ page }) => {
        await openWizardStepTwo(page);

        await fillSessionModal(page, 'WizardTestLocation', '20-09-2026', '8:00 AM', '10:00 AM');
        await page.click('#add-session-modal .modal-footer button.btn-primary');
        await page.waitForTimeout(300);
        await expect(page.locator('#wizard-sessions-tbody tr')).toHaveCount(1);

        await fillSessionModal(page, 'WizardTestLocation', '20-09-2026', '9:00 AM', '11:00 AM');
        await page.click('#add-session-modal .modal-footer button.btn-primary');
        await page.waitForTimeout(300);

        await expect(page.locator('#add-session-alert .session-callout--danger')).toBeVisible();
        await expect(page.locator('#conflict-hint-group')).toBeVisible();

        await page.click('#add-session-modal .modal-footer button.btn-default');
        await expect(page.locator('#wizard-sessions-tbody tr')).toHaveCount(1);
    });

    test('deletes the selected session from the edit modal', async ({ page }) => {
        await openWizardStepTwo(page);

        await fillSessionModal(page, 'WizardTestLocation', '20-09-2026', '8:00 AM', '10:00 AM');
        await page.click('#add-session-modal .modal-footer button.btn-primary');
        await page.waitForTimeout(300);

        await page.click('#wizard-sessions-tbody tr button[title="Editar sesión"]');
        await page.waitForSelector('#add-session-modal.in');
        await page.click('#add-session-delete-btn');
        await page.waitForSelector('#delete-session-modal.in');
        await page.click('#delete-session-confirm');
        await page.waitForTimeout(300);

        await expect(page.locator('#wizard-sessions-tbody')).toContainText('No hay sesiones agregadas');
    });

    test('repeats the session weekly the indicated number of times', async ({ page }) => {
        await openWizardStepTwo(page);

        await fillSessionModal(page, 'WizardTestLocation', '20-09-2026', '8:00 AM', '10:00 AM');
        await page.evaluate(() => {
            (window as any).jQuery('#repeat-count').val(3);
        });
        await page.click('#add-session-modal .modal-footer button.btn-primary');
        await page.waitForTimeout(300);

        const tbody = page.locator('#wizard-sessions-tbody');
        await expect(tbody.locator('tr')).toHaveCount(3);
        await expect(tbody).toContainText('20-09-2026');
        await expect(tbody).toContainText('27-09-2026');
        await expect(tbody).toContainText('04-10-2026');
    });

    test('shows course and facilitator on calendar session blocks', async ({ page }) => {
        await openWizardStepTwo(page);

        await fillSessionModal(page, 'WizardTestLocation', '20-09-2026', '8:00 AM', '10:00 AM');
        await page.click('#add-session-modal .modal-footer button.btn-primary');
        await page.waitForTimeout(400);

        const sub = page.locator('#wizard-calendar .fc-ev-sub').first();
        await expect(sub).toBeVisible({ timeout: 5000 });
        await expect(sub).toContainText('Wizard Test Course');
        await expect(sub).toContainText('Wizard Test');
    });

    test('blocks a repeat that would collide with an existing draft session', async ({ page }) => {
        await openWizardStepTwo(page);

        // First session on 20-09-2026
        await fillSessionModal(page, 'WizardTestLocation', '20-09-2026', '8:00 AM', '10:00 AM');
        await page.click('#add-session-modal .modal-footer button.btn-primary');
        await page.waitForTimeout(300);
        await expect(page.locator('#wizard-sessions-tbody tr')).toHaveCount(1);

        // One week earlier with repeat = 2: the second occurrence (20-09) collides
        await fillSessionModal(page, 'WizardTestLocation', '13-09-2026', '8:00 AM', '10:00 AM');
        await page.evaluate(() => {
            (window as any).jQuery('#repeat-count').val(2).trigger('change');
        });
        await page.waitForTimeout(200);

        // Live warning appears before saving
        await expect(page.locator('#conflict-hint-group')).toBeVisible();

        await page.click('#add-session-modal .modal-footer button.btn-primary');
        await page.waitForTimeout(300);
        await expect(page.locator('#add-session-alert .session-callout--danger')).toBeVisible();

        await page.click('#add-session-modal .modal-footer button.btn-default');
        await expect(page.locator('#wizard-sessions-tbody tr')).toHaveCount(1);
    });
});

test.describe('Location Filter Groups', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/af_programadas/crear');
        await page.waitForSelector('#wizard-form');
        await selectSelect2ById(page, 'wizard-titulo');
        await page.waitForTimeout(100);
        await selectSelect2ById(page, 'wizard-facilitador');
        await page.click('#wizard-btn-next');
        await page.waitForSelector('#panel-step2.active');
        await page.waitForSelector('#location-color-legend .loc-group');
    });

    test('renders building and floor groups', async ({ page }) => {
        await expect(page.locator('#location-color-legend .loc-group').first()).toBeVisible();
        await expect(page.locator('#location-color-legend .loc-floor-header').first()).toBeVisible();

        // Only the most-used building starts expanded
        const expandedBuildings = page.locator('#location-color-legend .loc-group-header[aria-expanded="true"]');
        await expect(expandedBuildings).toHaveCount(1);
    });

    test('collapses and expands a building group', async ({ page }) => {
        const group = page.locator('#location-color-legend .loc-group').first();
        const header = group.locator('.loc-group-header');

        await expect(header).toHaveAttribute('aria-expanded', 'true');
        await header.click();
        await expect(header).toHaveAttribute('aria-expanded', 'false');
        await expect(group.locator('.loc-group-body')).toHaveAttribute('hidden', '');

        await header.click();
        await expect(header).toHaveAttribute('aria-expanded', 'true');
        await expect(group.locator('.loc-group-body')).not.toHaveAttribute('hidden', '');
    });

    test('search filters chips and expands matching groups', async ({ page }) => {
        await page.fill('#location-chip-search', 'Aula 00');
        await page.waitForTimeout(250);

        const chips = page.locator('#location-color-legend .loc-chip:not(.loc-chip-all)');
        expect(await chips.count()).toBeGreaterThan(0);

        const texts = await chips.allTextContents();
        for (const text of texts) {
            expect(text.toLowerCase()).toContain('aula 00');
        }

        // Matching groups are expanded while searching
        await expect(page.locator('#location-color-legend .loc-group-header[aria-expanded="false"]')).toHaveCount(0);
    });
});

test.describe('Action Guards', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
    });

    test('cancel button is disabled for cancelled actions', async ({ page }) => {
        createScheduledWithStatus(4);
        await page.goto('/u/af_programadas');
        await page.waitForLoadState('load');

        const row = page.locator('.af-index table tbody tr').filter({ hasText: 'Cancelado' }).first();
        await expect(row).toBeVisible();
        await row.locator('a[title="Más funciones"]').click();
        await expect(row.locator('.dropdown-menu li', { hasText: 'Cancelar' })).toHaveClass(/disabled/);
    });

    test('sessions panel scrolls when many sessions are added', async ({ page }) => {
        await openWizardStepTwo(page);

        const dates = ['20-09-2026', '27-09-2026', '04-10-2026', '11-10-2026', '18-10-2026', '25-10-2026', '01-11-2026', '08-11-2026'];
        for (const date of dates) {
            await fillSessionModal(page, 'WizardTestLocation', date, '8:00 AM', '9:00 AM');
            await page.click('#add-session-modal .modal-footer button.btn-primary');
            await page.waitForTimeout(120);
        }

        const scrollable = await page.evaluate(() => {
            const el = document.querySelector('.step2-sessions-scroll') as HTMLElement | null;
            return el ? el.scrollHeight > el.clientHeight + 5 : false;
        });

        expect(scrollable).toBe(true);
    });
});
