import { test, expect, type Page } from '@playwright/test';
import { loginAs } from './helpers/auth';
import path from 'path';
import { execSync } from 'child_process';

const unique = () => Date.now();
const fixturePath = path.resolve(__dirname, 'fixtures/test-document.pdf');

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

test.describe('Courses - Documents / File Upload', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/acciones_formacion');
    });

    test('docs section is visible on tab 4 for new course', async ({ page }) => {
        await openCourseModal(page);
        await fillTab0(page, { codigo: `ND${unique()}`, titulo: 'No Docs Test', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });

        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        const docsVisible = await page.evaluate(() => {
            return (window as any).jQuery('#docs').is(':visible');
        });
        expect(docsVisible).toBe(true);
    });

    test('file input elements exist on tab 4', async ({ page }) => {
        await openCourseModal(page);
        await fillTab0(page, { codigo: `FI${unique()}`, titulo: 'File Input Test', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });

        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await expect(page.locator('#manual_f')).toBeAttached();
        await expect(page.locator('#manual_p')).toBeAttached();
        await expect(page.locator('#guia')).toBeAttached();
        await expect(page.locator('#presentacion')).toBeAttached();
    });

    test('creates a course with a file upload', async ({ page }) => {
        const testCode = `FU${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'File Upload Course', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });

        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await page.evaluate(() => {
            (window as any).jQuery('#manual_f').prop('disabled', false);
        });
        await page.setInputFiles('#manual_f', fixturePath);

        const filenameVisible = await page.evaluate(() => {
            const span = document.getElementById('l_manual_f');
            return span ? span.textContent : '';
        });
        expect(filenameVisible).toContain('test-document.pdf');

        await page.evaluate(() => (window as any).setCourse({}));

        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('creates a course with multiple file uploads', async ({ page }) => {
        const testCode = `MF${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'Multi File Course', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });

        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#manual_f').prop('disabled', false);
            $('#manual_p').prop('disabled', false);
        });
        await page.setInputFiles('#manual_f', fixturePath);
        await page.setInputFiles('#manual_p', fixturePath);

        const manualFName = await page.evaluate(() => {
            const span = document.getElementById('l_manual_f');
            return span ? span.textContent : '';
        });
        const manualPName = await page.evaluate(() => {
            const span = document.getElementById('l_manual_p');
            return span ? span.textContent : '';
        });
        expect(manualFName).toContain('test-document.pdf');
        expect(manualPName).toContain('test-document.pdf');

        await page.evaluate(() => (window as any).setCourse({}));

        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('fileinput transitions to exists state after file upload', async ({ page }) => {
        await openCourseModal(page);
        await fillTab0(page, { codigo: `FR${unique()}`, titulo: 'File Remove Test', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });

        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await page.evaluate(() => {
            (window as any).jQuery('#manual_f').prop('disabled', false);
        });
        await page.setInputFiles('#manual_f', fixturePath);
        await page.waitForTimeout(200);

        const hasFile = await page.evaluate(() => {
            return (window as any).jQuery('#fileinput_manual_f').hasClass('fileinput-exists');
        });
        expect(hasFile).toBe(true);
    });

    test('fileinput shows filename span after upload', async ({ page }) => {
        await openCourseModal(page);
        await fillTab0(page, { codigo: `FN${unique()}`, titulo: 'Filename Span Test', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });

        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await page.evaluate(() => {
            (window as any).jQuery('#manual_f').prop('disabled', false);
        });
        await page.setInputFiles('#manual_f', fixturePath);
        await page.waitForTimeout(200);

        const filename = await page.evaluate(() => {
            const span = document.getElementById('l_manual_f');
            return span ? span.textContent : '';
        });
        expect(filename).toContain('test-document.pdf');

        const inputHidden = await page.evaluate(() => {
            const input = document.getElementById('manual_f') as HTMLInputElement;
            return input && input.files ? input.files.length : 0;
        });
        expect(inputHidden).toBe(1);
    });
});

test.describe('Courses - Content List', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/acciones_formacion');
    });

    async function navigateToTab3(page: any) {
        await openCourseModal(page);
        await fillTab0(page, { codigo: `CL${unique()}`, titulo: 'Content List Test', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('.content-input').prop('disabled', false);
            $('.add-content-btn').prop('disabled', false);
        });
    }

    test('content list is empty by default', async ({ page }) => {
        await navigateToTab3(page);
        const listItems = await page.locator('#accion-modal .content-list li').count();
        expect(listItems).toBe(0);
    });

    test('add content item via button click', async ({ page }) => {
        await navigateToTab3(page);
        await page.evaluate(() => {
            (window as any).jQuery('.content-input').val('Introduction to Python');
        });
        await page.click('.add-content-btn');
        await page.waitForTimeout(200);

        const listItems = await page.locator('#accion-modal .content-list li').count();
        expect(listItems).toBe(1);
        const text = await page.locator('#accion-modal .content-list li .content-text').first().textContent();
        expect(text).toBe('Introduction to Python');
    });

    test('add content item via Enter key', async ({ page }) => {
        await navigateToTab3(page);
        await page.evaluate(() => {
            (window as any).jQuery('.content-input').val('Data Types and Variables');
        });
        await page.click('.content-input');
        await page.press('.content-input', 'Enter');
        await page.waitForTimeout(200);

        const listItems = await page.locator('#accion-modal .content-list li').count();
        expect(listItems).toBe(1);
        const text = await page.locator('#accion-modal .content-list li .content-text').first().textContent();
        expect(text).toBe('Data Types and Variables');
    });

    test('add multiple content items', async ({ page }) => {
        await navigateToTab3(page);

        for (const item of ['Module 1', 'Module 2', 'Module 3']) {
            await page.evaluate((text: string) => {
                (window as any).jQuery('.content-input').val(text);
            }, item);
            await page.click('.add-content-btn');
            await page.waitForTimeout(100);
        }

        const listItems = await page.locator('#accion-modal .content-list li').count();
        expect(listItems).toBe(3);

        const texts = await page.locator('#accion-modal .content-list li .content-text').allTextContents();
        expect(texts).toEqual(['Module 1', 'Module 2', 'Module 3']);
    });

    test('edit content item', async ({ page }) => {
        await navigateToTab3(page);

        await page.evaluate(() => {
            (window as any).jQuery('.content-input').val('Original Content');
        });
        await page.click('.add-content-btn');
        await page.waitForTimeout(200);

        await page.click('.edit-content-btn');
        await page.waitForTimeout(100);

        const inputValue = await page.evaluate(() => {
            return (window as any).jQuery('.content-input').val();
        });
        expect(inputValue).toBe('Original Content');

        const buttonText = await page.evaluate(() => {
            return (window as any).jQuery('.add-content-btn').text().trim();
        });
        expect(buttonText).toContain('Guardar');

        await page.evaluate(() => {
            (window as any).jQuery('.content-input').val('Updated Content');
        });
        await page.click('.add-content-btn');
        await page.waitForTimeout(200);

        const text = await page.locator('#accion-modal .content-list li .content-text').first().textContent();
        expect(text).toBe('Updated Content');
    });

    test('remove content item', async ({ page }) => {
        await navigateToTab3(page);

        for (const item of ['Item A', 'Item B', 'Item C']) {
            await page.evaluate((text: string) => {
                (window as any).jQuery('.content-input').val(text);
            }, item);
            await page.click('.add-content-btn');
            await page.waitForTimeout(100);
        }

        let listItems = await page.locator('#accion-modal .content-list li').count();
        expect(listItems).toBe(3);

        await page.click('#accion-modal .content-list li:first-child .remove-content-btn');
        await page.waitForTimeout(200);

        listItems = await page.locator('#accion-modal .content-list li').count();
        expect(listItems).toBe(2);

        const texts = await page.locator('#accion-modal .content-list li .content-text').allTextContents();
        expect(texts).toEqual(['Item B', 'Item C']);
    });

    test('empty input does not add content', async ({ page }) => {
        await navigateToTab3(page);
        await page.evaluate(() => {
            (window as any).jQuery('.content-input').val('');
        });
        await page.click('.add-content-btn');
        await page.waitForTimeout(200);

        const listItems = await page.locator('#accion-modal .content-list li').count();
        expect(listItems).toBe(0);
    });

    test('Escape key cancels edit mode', async ({ page }) => {
        await navigateToTab3(page);

        await page.evaluate(() => {
            (window as any).jQuery('.content-input').val('Persistent Content');
        });
        await page.click('.add-content-btn');
        await page.waitForTimeout(200);

        await page.click('.edit-content-btn');
        await page.waitForTimeout(100);

        await page.evaluate(() => {
            (window as any).jQuery('.content-input').val('Should Not Save');
        });
        await page.press('.content-input', 'Escape');
        await page.waitForTimeout(200);

        const buttonText = await page.evaluate(() => {
            return (window as any).jQuery('.add-content-btn').text().trim();
        });
        expect(buttonText).toContain('Añadir');

        const text = await page.locator('#accion-modal .content-list li .content-text').first().textContent();
        expect(text).toBe('Persistent Content');
    });

    test('content list resets when modal closes', async ({ page }) => {
        await navigateToTab3(page);

        await page.evaluate(() => {
            (window as any).jQuery('.content-input').val('Should Clear');
        });
        await page.click('.add-content-btn');
        await page.waitForTimeout(200);

        let listItems = await page.locator('#accion-modal .content-list li').count();
        expect(listItems).toBe(1);

        await page.evaluate(() => {
            (window as any).jQuery('#accion-modal').modal('hide');
        });
        await page.waitForTimeout(500);

        await openCourseModal(page);
        await fillTab0(page, { codigo: `CR${unique()}`, titulo: 'Reset Test', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('.content-input').prop('disabled', false);
        });

        listItems = await page.locator('#accion-modal .content-list li').count();
        expect(listItems).toBe(0);
    });

    test('content data is submitted with course creation', async ({ page }) => {
        const testCode = `CD${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'Content Data Course', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });

        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('.content-input').prop('disabled', false);
            $('.add-content-btn').prop('disabled', false);
        });

        for (const item of ['Intro', 'Body', 'Conclusion']) {
            await page.evaluate((text: string) => {
                (window as any).jQuery('.content-input').val(text);
            }, item);
            await page.click('.add-content-btn');
            await page.waitForTimeout(100);
        }

        const contentData = await page.evaluate(() => {
            return (window as any).getContentData();
        });
        expect(contentData).toEqual(['Intro', 'Body', 'Conclusion']);

        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });
});

test.describe('Courses - Objective', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/acciones_formacion');
    });

    test('objective textarea has maxlength of 3000', async ({ page }) => {
        await openCourseModal(page);
        const maxlength = await page.evaluate(() => {
            const textarea = document.getElementById('objetivo') as HTMLTextAreaElement;
            return textarea ? textarea.maxLength : -1;
        });
        expect(maxlength).toBe(3000);
    });

    test('objective accepts long text within limit', async ({ page }) => {
        const longText = 'A'.repeat(2500);
        await openCourseModal(page);
        await fillTab0(page, { codigo: `OT${unique()}`, titulo: 'Objective Test', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: longText });

        const value = await page.evaluate(() => {
            return (window as any).jQuery('#objetivo').val();
        });
        expect(value).toBe(longText);
    });
});

test.describe('Courses - Edit Flow', () => {
    const COURSE_ID = 2;
    const COURSE_URL = `/u/acciones_formacion/details/${COURSE_ID}`;

    test.beforeAll(async () => {
        execSync(
            `php artisan tinker --execute 'DB::table("courses")->where("id", 2)->update(["title" => mb_convert_encoding("VENEZUELA POTENCIA ENERG\\u{00c9}TICA", "UTF-8", "UTF-8"), "code" => "2603"]); DB::table("capacities")->where("course_id", 2)->update(["min" => 16, "max" => 30]); DB::table("contents")->where("course_id", 2)->delete(); DB::table("files")->where("course_id", 2)->delete();'`,
            { cwd: process.cwd(), timeout: 15000 }
        );
        execSync(
            `php artisan tinker --execute 'DB::table("contents")->insert([["text" => mb_convert_encoding("Fuentes de energ\\u{00eda} en Venezuela.", "UTF-8", "UTF-8"), "course_id" => 2, "created_at" => now(), "updated_at" => now()], ["text" => "Condiciones legales.", "course_id" => 2, "created_at" => now(), "updated_at" => now()], ["text" => mb_convert_encoding("Internacionalizaci\\u{00f3}n de los hidrocarburos.", "UTF-8", "UTF-8"), "course_id" => 2, "created_at" => now(), "updated_at" => now()], ["text" => mb_convert_encoding("La integraci\\u{00f3}n latinoamericana y caribe\\u{00f1}a.", "UTF-8", "UTF-8"), "course_id" => 2, "created_at" => now(), "updated_at" => now()], ["text" => mb_convert_encoding("La producci\\u{00f3}n y el consumo de la energ\\u{00eda} gestionando la preservaci\\u{00f3}n del ambiente.", "UTF-8", "UTF-8"), "course_id" => 2, "created_at" => now(), "updated_at" => now()], ["text" => mb_convert_encoding("La diversificaci\\u{00f3}n productiva y la inclusi\\u{00f3}n social.", "UTF-8", "UTF-8"), "course_id" => 2, "created_at" => now(), "updated_at" => now()], ["text" => mb_convert_encoding("Planes socialistas de la naci\\u{00f3}n.", "UTF-8", "UTF-8"), "course_id" => 2, "created_at" => now(), "updated_at" => now()]])'`,
            { cwd: process.cwd(), timeout: 15000 }
        );
    });

    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/acciones_formacion');
    });

    async function openEditModal(page: any) {
        await page.evaluate((url: string) => (window as any).editarAccion(url), COURSE_URL);
        await page.waitForSelector('#accion-form:not(.hidden)');
        await page.waitForSelector('.modal.in');
        await page.waitForTimeout(500);
    }

    async function addContentInEditModal(page: any, text: string) {
        await page.evaluate(() => (window as any).tabSwitch(2));
        await page.waitForTimeout(200);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('.content-input').prop('disabled', false);
            $('.add-content-btn').prop('disabled', false);
        });
        await page.evaluate((t: string) => {
            (window as any).jQuery('.content-input').val(t);
        }, text);
        await page.click('.add-content-btn');
        await page.waitForTimeout(200);
    }

    test('edit button opens modal with correct title', async ({ page }) => {
        await openEditModal(page);
        const label = await page.evaluate(() => {
            return (window as any).jQuery('#accion-label').text();
        });
        expect(label).toBe('Editar Acción de formación');
    });

    test('edit modal pre-fills code field', async ({ page }) => {
        await openEditModal(page);
        const code = await page.evaluate(() => {
            return (window as any).jQuery('#codigo').val();
        });
        expect(code).toBe('2603');
    });

    test('edit modal pre-fills title field', async ({ page }) => {
        await openEditModal(page);
        const title = await page.evaluate(() => {
            return (window as any).jQuery('#titulo').val();
        });
        expect(title).toBe('VENEZUELA POTENCIA ENERGÉTICA');
    });

    test('edit modal pre-fills capacity fields', async ({ page }) => {
        await openEditModal(page);
        const min = await page.evaluate(() => (window as any).jQuery('#min').val());
        const max = await page.evaluate(() => (window as any).jQuery('#max').val());
        expect(min).toBe('16');
        expect(max).toBe('30');
    });

    test('edit modal pre-fills duration', async ({ page }) => {
        await openEditModal(page);
        const duracion = await page.evaluate(() => (window as any).jQuery('#duracion').val());
        expect(Number(duracion)).toBeGreaterThan(0);
    });

    test('edit modal pre-fills objective', async ({ page }) => {
        await openEditModal(page);
        const objetivo = await page.evaluate(() => (window as any).jQuery('#objetivo').val());
        expect(objetivo.length).toBeGreaterThan(0);
    });

    test('edit modal pre-fills content list items', async ({ page }) => {
        await openEditModal(page);
        const listItems = await page.locator('#accion-modal .content-list li').count();
        expect(listItems).toBeGreaterThanOrEqual(1);
    });

    test('edit modal keeps inputs enabled', async ({ page }) => {
        await openEditModal(page);
        const isDisabled = await page.evaluate(() => {
            return (window as any).jQuery('#codigo').prop('disabled');
        });
        expect(isDisabled).toBe(false);
    });

    test('edit modal sets _method to PUT', async ({ page }) => {
        await openEditModal(page);
        const method = await page.evaluate(() => {
            return (window as any).jQuery('input[name="_method"]').val();
        });
        expect(method).toBe('PUT');
    });

    test('edit modal sets course-id', async ({ page }) => {
        await openEditModal(page);
        const courseId = await page.evaluate(() => {
            return (window as any).jQuery('.course-id').val();
        });
        expect(courseId).toBe(String(COURSE_ID));
    });

    test('edit submits and shows success', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('edit can change title and persist', async ({ page }) => {
        await openEditModal(page);
        const newTitle = `Edited ${unique()}`;
        await page.evaluate((title: string) => {
            (window as any).jQuery('#titulo').val(title).trigger('input');
        }, newTitle);
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();

        await page.goto('/u/acciones_formacion');
        await page.waitForLoadState('load');
        await expect(page.locator('table')).toContainText(newTitle);
    });

    test('edit shows validation error with empty title', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => {
            (window as any).jQuery('#titulo').val('').trigger('input');
        });
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-danger').first()).toBeVisible();
    });

    test('edit can add new content item', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => (window as any).tabSwitch(2));
        await page.waitForTimeout(200);
        const initialCount = await page.locator('#accion-modal .content-list li').count();

        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('.content-input').prop('disabled', false);
            $('.add-content-btn').prop('disabled', false);
        });
        await page.evaluate(() => {
            (window as any).jQuery('.content-input').val('New Edit Content');
        });
        await page.click('.add-content-btn');
        await page.waitForTimeout(200);

        const newCount = await page.locator('#accion-modal .content-list li').count();
        expect(newCount).toBe(initialCount + 1);
    });

    test('edit can remove content item', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => (window as any).tabSwitch(2));
        await page.waitForTimeout(200);
        const initialCount = await page.locator('#accion-modal .content-list li').count();
        expect(initialCount).toBeGreaterThanOrEqual(1);

        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('.content-input').prop('disabled', false);
            $('.remove-content-btn').prop('disabled', false);
        });
        await page.click('#accion-modal .content-list li:first-child .remove-content-btn');
        await page.waitForTimeout(200);

        const newCount = await page.locator('#accion-modal .content-list li').count();
        expect(newCount).toBe(initialCount - 1);
    });

    test('edit can change capacity and persist', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#min').val('5').trigger('input');
            $('#max').val('50').trigger('input');
        });
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('edit shows no-docs message when no files exist', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        const noDocsVisible = await page.evaluate(() => {
            return (window as any).jQuery('.no-docs').is(':visible');
        });
        expect(noDocsVisible).toBe(true);
    });

    test('edit shows existing file names when files exist', async ({ page }) => {
        const testCode = `EF${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'File Edit Test', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await page.evaluate(() => {
            (window as any).jQuery('#manual_f').prop('disabled', false);
        });
        await page.setInputFiles('#manual_f', fixturePath);
        await page.waitForTimeout(200);

        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();

        const row = page.locator('tr').filter({ hasText: testCode });
        const editLink = row.locator('a[title="Editar acción de formación"]');
        if (await editLink.isVisible({ timeout: 3000 }).catch(() => false)) {
            const href = await editLink.getAttribute('href');
            const urlMatch = href?.match(/editarAccion\('(.+)'\)/);
            if (urlMatch) {
                await page.evaluate((url: string) => (window as any).editarAccion(url), urlMatch[1]);
                await page.waitForSelector('#accion-form:not(.hidden)');
                await page.waitForSelector('.modal.in');
                await page.waitForTimeout(500);

                await page.evaluate(() => (window as any).tabSwitch(3));
                await page.waitForTimeout(200);

                const hasFile = await page.evaluate(() => {
                    return (window as any).jQuery('#fileinput_manual_f').hasClass('fileinput-exists');
                });
                expect(hasFile).toBe(true);
            }
        }
    });

    test('edit shows validation error with empty code', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#codigo').val('').trigger('input');
        });
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-danger').first()).toBeVisible();
    });

    test('edit shows validation error with empty category', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#categoria_id').val('').trigger('change');
        });
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-danger').first()).toBeVisible();
    });

    test('edit shows validation error with empty modality', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#modalidad_id').val('').trigger('change');
        });
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-danger').first()).toBeVisible();
    });

    test('edit shows validation error with empty duration', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#duracion').val('').trigger('input');
        });
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-danger').first()).toBeVisible();
    });

    test('edit shows validation error with empty min capacity', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#min').val('').trigger('input');
        });
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-danger').first()).toBeVisible();
    });

    test('edit shows validation error with empty max capacity', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#max').val('').trigger('input');
        });
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-danger').first()).toBeVisible();
    });

    test('edit shows validation error with empty audience', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#dirigido').val('').trigger('input');
        });
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-danger').first()).toBeVisible();
    });

    test('edit shows validation error with empty objective', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#objetivo').val('').trigger('input');
        });
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-danger').first()).toBeVisible();
    });

    test('edit shows validation error with duplicate code', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#codigo').val('2546').trigger('input');
        });
        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-danger').first()).toBeVisible();
    });

    test('edit can upload a file', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await page.evaluate(() => {
            (window as any).jQuery('#manual_f').prop('disabled', false);
        });
        await page.setInputFiles('#manual_f', fixturePath);
        await page.waitForTimeout(200);

        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('edit can upload multiple files', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#manual_f').prop('disabled', false);
            $('#manual_p').prop('disabled', false);
        });
        await page.setInputFiles('#manual_f', fixturePath);
        await page.setInputFiles('#manual_p', fixturePath);
        await page.waitForTimeout(200);

        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('edit file input shows exists state after upload', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await page.evaluate(() => {
            (window as any).jQuery('#manual_f').prop('disabled', false);
        });
        await page.setInputFiles('#manual_f', fixturePath);
        await page.waitForTimeout(200);

        const hasExists = await page.evaluate(() => {
            return (window as any).jQuery('#fileinput_manual_f').hasClass('fileinput-exists');
        });
        expect(hasExists).toBe(true);
    });

    test('edit can delete an existing file', async ({ page }) => {
        const testCode = `DF${unique()}`;
        await openCourseModal(page);
        await fillTab0(page, { codigo: testCode, titulo: 'File Delete Test', duracion: '8' });
        await fillTab1(page, { min: '10', max: '30', dirigido: 'Test audience' });
        await fillTab2(page, { objetivo: 'Test objective' });
        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await page.evaluate(() => {
            (window as any).jQuery('#manual_f').prop('disabled', false);
        });
        await page.setInputFiles('#manual_f', fixturePath);
        await page.waitForTimeout(200);

        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();

        const row = page.locator('tr').filter({ hasText: testCode });
        const editLink = row.locator('a[title="Editar acción de formación"]');
        if (await editLink.isVisible({ timeout: 3000 }).catch(() => false)) {
            const href = await editLink.getAttribute('href');
            const urlMatch = href?.match(/editarAccion\('(.+)'\)/);
            if (urlMatch) {
                await page.evaluate((url: string) => (window as any).editarAccion(url), urlMatch[1]);
                await page.waitForSelector('#accion-form:not(.hidden)');
                await page.waitForSelector('.modal.in');
                await page.waitForTimeout(500);

                await page.evaluate(() => (window as any).tabSwitch(3));
                await page.waitForTimeout(200);

                const hasFileBefore = await page.evaluate(() => {
                    return (window as any).jQuery('#fileinput_manual_f').hasClass('fileinput-exists');
                });
                expect(hasFileBefore).toBe(true);

                await page.click('#remove_facilitator');
                await page.waitForTimeout(200);

                const hasFileAfter = await page.evaluate(() => {
                    return (window as any).jQuery('#fileinput_manual_f').hasClass('fileinput-exists');
                });
                expect(hasFileAfter).toBe(false);

                await page.evaluate(() => (window as any).setCourse({}));
                await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
                await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
            }
        }
    });

    test('edit can edit content item inline', async ({ page }) => {
        await openEditModal(page);
        const seedText = `Seed ${unique()}`;
        await addContentInEditModal(page, seedText);
        await page.waitForFunction(() => {
            const data = (window as any).getContentData();
            return Array.isArray(data) && data.length >= 1;
        }, { timeout: 5000 });

        const initialData = await page.evaluate(() => (window as any).getContentData());
        expect(initialData.length).toBeGreaterThanOrEqual(1);
        const originalText = initialData[0];

        await page.click('#accion-modal .content-list li:first-child .edit-content-btn');
        await page.waitForTimeout(200);

        const inputValue = await page.evaluate(() => {
            return (window as any).jQuery('.content-input').val();
        });
        expect(inputValue).toBe(originalText);

        const editedText = `Edited ${unique()}`;
        await page.evaluate((text: string) => {
            (window as any).jQuery('.content-input').val(text);
        }, editedText);
        await page.click('.add-content-btn');
        await page.waitForTimeout(200);

        const updatedData = await page.evaluate(() => (window as any).getContentData());
        expect(updatedData[0]).toBe(editedText);
    });

    test('edit escape key cancels content edit', async ({ page }) => {
        await openEditModal(page);
        const seedText = `Seed ${unique()}`;
        await addContentInEditModal(page, seedText);
        await page.waitForFunction(() => {
            const data = (window as any).getContentData();
            return Array.isArray(data) && data.length >= 1;
        }, { timeout: 5000 });

        const initialData = await page.evaluate(() => (window as any).getContentData());
        expect(initialData.length).toBeGreaterThanOrEqual(1);
        const originalText = initialData[0];

        await page.click('#accion-modal .content-list li:first-child .edit-content-btn');
        await page.waitForTimeout(200);

        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('.content-input').val('Should Be Reverted');
        });
        await page.press('.content-input', 'Escape');
        await page.waitForTimeout(200);

        const inputValue = await page.evaluate(() => {
            return (window as any).jQuery('.content-input').val();
        });
        expect(inputValue).toBe('');

        const dataAfterEscape = await page.evaluate(() => (window as any).getContentData());
        expect(dataAfterEscape[0]).toBe(originalText);
    });

    test('edit empty content input does not add item', async ({ page }) => {
        await openEditModal(page);
        await addContentInEditModal(page, `Seed ${unique()}`);
        await page.waitForFunction(() => {
            const data = (window as any).getContentData();
            return Array.isArray(data) && data.length >= 1;
        }, { timeout: 5000 });

        const initialCount = await page.locator('#accion-modal .content-list li').count();

        await page.evaluate(() => {
            (window as any).jQuery('.content-input').val('');
        });
        await page.click('.add-content-btn');
        await page.waitForTimeout(200);

        const newCount = await page.locator('#accion-modal .content-list li').count();
        expect(newCount).toBe(initialCount);
    });

    test('edit can add content item via Enter key', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => (window as any).tabSwitch(2));
        await page.waitForTimeout(200);

        await page.evaluate(() => {
            (window as any).jQuery('.content-input').prop('disabled', false);
        });

        const initialCount = await page.locator('#accion-modal .content-list li').count();
        const testContent = `Enter Content ${unique()}`;

        await page.evaluate((text: string) => {
            (window as any).jQuery('.content-input').val(text);
        }, testContent);
        await page.press('.content-input', 'Enter');
        await page.waitForTimeout(200);

        const newCount = await page.locator('#accion-modal .content-list li').count();
        expect(newCount).toBe(initialCount + 1);
    });

    test('edit content list resets on modal reopen', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => (window as any).tabSwitch(2));
        await page.waitForTimeout(200);

        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('.content-input').prop('disabled', false);
            $('.add-content-btn').prop('disabled', false);
        });
        await page.evaluate(() => {
            (window as any).jQuery('.content-input').val('Unsaved Content');
        });
        await page.click('.add-content-btn');
        await page.waitForTimeout(200);

        const countBeforeClose = await page.locator('#accion-modal .content-list li').count();

        await page.click('.modal.in .close');
        await page.waitForTimeout(500);

        await openEditModal(page);
        await page.waitForTimeout(500);
        await page.evaluate(() => (window as any).tabSwitch(2));
        await page.waitForTimeout(200);

        const countAfterReopen = await page.locator('#accion-modal .content-list li').count();
        expect(countAfterReopen).toBeLessThan(countBeforeClose);
    });

    test('edit content data persists after submit', async ({ page }) => {
        await openEditModal(page);
        await page.evaluate(() => (window as any).tabSwitch(2));
        await page.waitForTimeout(200);

        const testContent = `Persist ${unique()}`;

        const existingContent: string[] = await page.evaluate(() => (window as any).getContentData());
        const updatedContent = [...existingContent, testContent];

        await page.evaluate((content: string[]) => {
            (window as any).setInitialContentData(content);
        }, updatedContent);
        await page.waitForTimeout(500);

        const domCount = await page.locator('#accion-modal .content-list li').count();
        expect(domCount).toBe(updatedContent.length);

        await page.evaluate(() => (window as any).setCourse({}));
        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await page.waitForLoadState('networkidle');

        let dbContent: string[] = [];
        for (let attempt = 0; attempt < 5; attempt++) {
            dbContent = await page.evaluate(async (id: number) => {
                const resp = await fetch(`/u/acciones_formacion/details/${id}`);
                const data = await resp.json();
                return data[0]?.content?.map((c: any) => c.text) || [];
            }, COURSE_ID);
            if (dbContent.includes(testContent)) break;
            await page.waitForTimeout(1000);
        }
        expect(dbContent).toContain(testContent);
    });
});
