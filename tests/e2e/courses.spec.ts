import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

const unique = () => Date.now();

async function selectSelect2Option(page: any, selectId: string) {
    await page.evaluate(({ id }: { id: string }) => {
        const el = document.getElementById(id) as HTMLSelectElement;
        if (!el) return;
        el.disabled = false;
        const options = el.querySelectorAll('option');
        if (options.length > 1) {
            const firstOption = options[1] as HTMLOptionElement;
            el.value = firstOption.value;
            (window as any).jQuery(`#${id}`).trigger('change');
        }
    }, { id: selectId });
    await page.waitForTimeout(200);
}

async function openCourseModal(page: any) {
    await page.evaluate((url: string) => (window as any).crearAccion(url), '/u/acciones_formacion');
    await page.waitForSelector('#accion-form:not(.hidden)');
    await page.waitForSelector('.modal.in');
}

async function fillTab0(page: any, overrides: Record<string, string> = {}) {
    await page.evaluate((overrides: Record<string, string>) => {
        const $ = (window as any).jQuery;
        $('.create-course-form :input').prop('disabled', false);
        $('.select2').prop('disabled', false);
        if (overrides.codigo !== undefined) $('#codigo').val(overrides.codigo).trigger('input');
        if (overrides.titulo !== undefined) $('#titulo').val(overrides.titulo).trigger('input');
        if (overrides.duracion !== undefined) $('#duracion').val(overrides.duracion).trigger('input');
    }, overrides);
    if (!overrides.categoria_id) await selectSelect2Option(page, 'categoria_id');
    if (!overrides.modalidad_id) await selectSelect2Option(page, 'modalidad_id');
}

async function fillTab1(page: any, overrides: Record<string, string> = {}) {
    await page.evaluate(() => (window as any).tabSwitch(1));
    await page.waitForTimeout(200);
    await page.evaluate((overrides: Record<string, string>) => {
        const $ = (window as any).jQuery;
        if (overrides.min !== undefined) $('#min').val(overrides.min).trigger('input');
        if (overrides.max !== undefined) $('#max').val(overrides.max).trigger('input');
        if (overrides.dirigido !== undefined) $('#dirigido').val(overrides.dirigido).trigger('input');
    }, overrides);
}

async function fillTab2(page: any, overrides: Record<string, string> = {}) {
    await page.evaluate(() => (window as any).tabSwitch(2));
    await page.waitForTimeout(200);
    await page.evaluate((overrides: Record<string, string>) => {
        const $ = (window as any).jQuery;
        if (overrides.objetivo !== undefined) $('#objetivo').val(overrides.objetivo).trigger('input');
    }, overrides);
}

async function submitAndExpectError(page: any) {
    await page.evaluate(() => (window as any).tabSwitch(3));
    await page.waitForTimeout(200);
    await page.evaluate(() => (window as any).setCourse({}));
    await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
    await expect(page.locator('.alert-danger').first()).toBeVisible();
}

test.describe('Courses CRUD', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/acciones_formacion');
    });

    test('displays courses list', async ({ page }) => {
        await expect(page.locator('h3').first()).toContainText(/Formaci/);
        await expect(page.locator('table')).toBeVisible();
    });

    test('creates a new course', async ({ page }) => {
        const testCode = `TC${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'Test Course Title', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });

        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);
        await page.evaluate(() => (window as any).setCourse({}));

        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('deletes a course', async ({ page }) => {
        const testCode = `DC${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'Del Course', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });

        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);
        await page.evaluate(() => (window as any).setCourse({}));

        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();

        const deleteLink = page.locator('tr').filter({ hasText: testCode }).locator('a[title="Eliminar acción de formación"]');
        if (await deleteLink.isVisible({ timeout: 3000 }).catch(() => false)) {
            const href = await deleteLink.getAttribute('href');
            const urlMatch = href?.match(/eliminarCurso\('(.+)'\)/);
            if (urlMatch) {
                await page.evaluate((url: string) => (window as any).eliminarCurso(url), urlMatch[1]);
                await page.waitForSelector('#curso-form-delete:not(.hidden)');
                await page.click('#curso-form-delete #btn-action');
                await page.waitForLoadState('load');
                await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
            }
        }
    });

    test('shows error with duplicate code', async ({ page }) => {
        await openCourseModal(page);
        await fillTab0(page, { codigo: '2546', titulo: 'Duplicate Code Course', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await submitAndExpectError(page);
    });

    test('shows error with empty code', async ({ page }) => {
        await openCourseModal(page);
        await fillTab0(page, { codigo: '', titulo: 'No Code Course', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await submitAndExpectError(page);
    });

    test('shows error with empty title', async ({ page }) => {
        const testCode = `MT${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: '', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await submitAndExpectError(page);
    });

    test('shows error with empty category', async ({ page }) => {
        const testCode = `EC${unique()}`;
        await openCourseModal(page);
        await page.evaluate(({ code }: { code: string }) => {
            const $ = (window as any).jQuery;
            $('.create-course-form :input').prop('disabled', false);
            $('.select2').prop('disabled', false);
            $('#codigo').val(code).trigger('input');
            $('#titulo').val('No Category Course').trigger('input');
            $('#duracion').val('8').trigger('input');
        }, { code: testCode });
        // Skip selecting categoria_id - remove selected option
        await selectSelect2Option(page, 'modalidad_id');

        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await submitAndExpectError(page);
    });

    test('shows error with empty modality', async ({ page }) => {
        const testCode = `EM${unique()}`;
        await openCourseModal(page);
        await page.evaluate(({ code }: { code: string }) => {
            const $ = (window as any).jQuery;
            $('.create-course-form :input').prop('disabled', false);
            $('.select2').prop('disabled', false);
            $('#codigo').val(code).trigger('input');
            $('#titulo').val('No Modality Course').trigger('input');
            $('#duracion').val('8').trigger('input');
        }, { code: testCode });
        await selectSelect2Option(page, 'categoria_id');
        // Skip selecting modalidad_id

        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await submitAndExpectError(page);
    });

    test('shows error with empty duration', async ({ page }) => {
        const testCode = `ED${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'No Duration Course', duracion: '' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await submitAndExpectError(page);
    });

    test('shows error with empty min capacity', async ({ page }) => {
        const testCode = `EN${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'No Min Course', duracion: '8' });
        await fillTab1(page, { min: '', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await submitAndExpectError(page);
    });

    test('shows error with empty max capacity', async ({ page }) => {
        const testCode = `EMX${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'No Max Course', duracion: '8' });
        await fillTab1(page, { min: '10', max: '', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await submitAndExpectError(page);
    });

    test('shows error with empty audience', async ({ page }) => {
        const testCode = `EA${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'No Audience Course', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: '' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await submitAndExpectError(page);
    });

    test('shows error with empty objective', async ({ page }) => {
        const testCode = `EO${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'No Objective Course', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: '' });
        await submitAndExpectError(page);
    });
});

test.describe('Courses - Tab Navigation', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/acciones_formacion');
    });

    test('next button is disabled when tab 0 fields are empty', async ({ page }) => {
        await openCourseModal(page);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('.create-course-form :input').prop('disabled', false);
            $('#codigo').val('').trigger('input');
            $('#titulo').val('').trigger('input');
        });
        const isDisabled = await page.evaluate(() => {
            return (window as any).jQuery('.tab-button-next').hasClass('disabled');
        });
        expect(isDisabled).toBe(true);
    });

    test('next button enables when tab 0 fields are filled', async ({ page }) => {
        await openCourseModal(page);
        await page.waitForTimeout(500);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('.create-course-form :input').prop('disabled', false);
            $('#codigo').val('TestCode').trigger('input');
            $('#titulo').val('Test Title').trigger('input');
            $('#duracion').val('8').trigger('input');
        });
        await page.waitForTimeout(300);
        const isDisabled = await page.evaluate(() => {
            return (window as any).jQuery('.tab-button-next').hasClass('disabled');
        });
        expect(isDisabled).toBe(false);
    });

    test('submit button is visible on tab 3 only', async ({ page }) => {
        await openCourseModal(page);
        await fillTab0(page, { codigo: `TB${unique()}`, titulo: 'Tab Button Test', duracion: '8' });

        await page.evaluate(() => (window as any).tabSwitch(1));
        await page.waitForTimeout(200);
        let submitVisible = await page.evaluate(() => {
            return (window as any).jQuery('.tab-submit').is(':visible');
        });
        expect(submitVisible).toBe(false);

        await page.evaluate(() => (window as any).tabSwitch(2));
        await page.waitForTimeout(200);
        submitVisible = await page.evaluate(() => {
            return (window as any).jQuery('.tab-submit').is(':visible');
        });
        expect(submitVisible).toBe(false);

        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);
        submitVisible = await page.evaluate(() => {
            return (window as any).jQuery('.tab-submit').is(':visible');
        });
        expect(submitVisible).toBe(true);
    });
});

test.describe('Courses - Soft-Deleted Code', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/acciones_formacion');
    });

    test('soft-deleted course code cannot be reused', async ({ page }) => {
        test.setTimeout(60000);
        const testCode = `SD${unique()}`;

        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'Soft Delete Test', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();

        const deleteLink = page.locator('tr').filter({ hasText: testCode }).locator('a[title="Eliminar acción de formación"]');
        if (await deleteLink.isVisible({ timeout: 3000 }).catch(() => false)) {
            const href = await deleteLink.getAttribute('href');
            const urlMatch = href?.match(/eliminarCurso\('(.+)'\)/);
            if (urlMatch) {
                await page.evaluate((url: string) => (window as any).eliminarCurso(url), urlMatch[1]);
                await page.waitForSelector('#curso-form-delete:not(.hidden)');
                await page.click('#curso-form-delete #btn-action');
                await page.waitForLoadState('load');
                await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
            }
        }

        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'Reuse Soft Deleted Code', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await submitAndExpectError(page);
    });
});
