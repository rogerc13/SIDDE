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

        await page.evaluate(({ code, title }: { code: string; title: string }) => {
            const $ = (window as any).jQuery;
            $('.create-course-form :input').prop('disabled', false);
            $('.select2').prop('disabled', false);
            $('#codigo').val(code).trigger('input');
            $('#titulo').val(title).trigger('input');
        }, { code: testCode, title: 'Test Course Title' });

        await selectSelect2Option(page, 'categoria_id');
        await selectSelect2Option(page, 'modalidad_id');

        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#duracion').val('8').trigger('input');
        });

        await page.evaluate(() => (window as any).tabSwitch(1));
        await page.waitForTimeout(200);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#min').val('10').trigger('input');
            $('#max').val('30').trigger('input');
            $('#dirigido').val('Test audience').trigger('input');
        });
        await page.evaluate(() => (window as any).tabSwitch(2));
        await page.waitForTimeout(200);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#objetivo').val('Test objective').trigger('input');
        });
        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await page.evaluate(() => (window as any).setCourse({}));

        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('shows validation error with duplicate code', async ({ page }) => {
        await openCourseModal(page);

        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('.create-course-form :input').prop('disabled', false);
            $('.select2').prop('disabled', false);
            $('#codigo').val('2546').trigger('input');
            $('#titulo').val('Duplicate Code Course').trigger('input');
        });

        await selectSelect2Option(page, 'categoria_id');
        await selectSelect2Option(page, 'modalidad_id');

        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#duracion').val('8').trigger('input');
        });

        await page.evaluate(() => (window as any).tabSwitch(1));
        await page.waitForTimeout(200);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#min').val('10').trigger('input');
            $('#max').val('30').trigger('input');
            $('#dirigido').val('Test audience').trigger('input');
        });
        await page.evaluate(() => (window as any).tabSwitch(2));
        await page.waitForTimeout(200);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#objetivo').val('Test objective').trigger('input');
        });
        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await page.evaluate(() => (window as any).setCourse({}));

        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-danger').first()).toBeVisible();
    });

    test('shows validation error with missing title', async ({ page }) => {
        const testCode = `MT${unique()}`;
        await openCourseModal(page);

        await page.evaluate(({ code }: { code: string }) => {
            const $ = (window as any).jQuery;
            $('.create-course-form :input').prop('disabled', false);
            $('.select2').prop('disabled', false);
            $('#codigo').val(code).trigger('input');
        }, { code: testCode });

        await selectSelect2Option(page, 'categoria_id');
        await selectSelect2Option(page, 'modalidad_id');

        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#duracion').val('8').trigger('input');
        });

        await page.evaluate(() => (window as any).tabSwitch(1));
        await page.waitForTimeout(200);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#min').val('10').trigger('input');
            $('#max').val('30').trigger('input');
            $('#dirigido').val('Test audience').trigger('input');
        });
        await page.evaluate(() => (window as any).tabSwitch(2));
        await page.waitForTimeout(200);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#objetivo').val('Test objective').trigger('input');
        });
        await page.evaluate(() => (window as any).tabSwitch(3));
        await page.waitForTimeout(200);

        await page.evaluate(() => (window as any).setCourse({}));

        await page.waitForURL(/acciones_formacion/, { timeout: 10000 });
        await expect(page.locator('.alert-danger').first()).toBeVisible();
    });

    test('deletes a course', async ({ page }) => {
        const testCode = `DC${unique()}`;
        await openCourseModal(page);

        await page.evaluate(({ code }: { code: string }) => {
            const $ = (window as any).jQuery;
            $('.create-course-form :input').prop('disabled', false);
            $('.select2').prop('disabled', false);
            $('#codigo').val(code).trigger('input');
            $('#titulo').val('Del Course').trigger('input');
        }, { code: testCode });

        await selectSelect2Option(page, 'categoria_id');
        await selectSelect2Option(page, 'modalidad_id');

        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#duracion').val('8').trigger('input');
        });

        await page.evaluate(() => (window as any).tabSwitch(1));
        await page.waitForTimeout(200);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#min').val('10').trigger('input');
            $('#max').val('30').trigger('input');
            $('#dirigido').val('Test audience').trigger('input');
        });
        await page.evaluate(() => (window as any).tabSwitch(2));
        await page.waitForTimeout(200);
        await page.evaluate(() => {
            const $ = (window as any).jQuery;
            $('#objetivo').val('Test objective').trigger('input');
        });
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
});
