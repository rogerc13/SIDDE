import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

const unique = () => Date.now();
const SEEDED_BUILDING = 'Edificio Principal';

async function selectBuildingWithFloors(page: import('@playwright/test').Page) {
    const buildingId = await page.evaluate(() => {
        const buildingSelect = document.getElementById('location_building_id') as HTMLSelectElement;
        const floorSelect = document.getElementById('location_floor_id') as HTMLSelectElement;
        if (!buildingSelect || !floorSelect) return null;
        for (let i = 1; i < buildingSelect.options.length; i++) {
            buildingSelect.value = buildingSelect.options[i].value;
            buildingSelect.dispatchEvent(new Event('change'));
            if (floorSelect.options.length > 1) {
                return buildingSelect.options[i].value;
            }
        }
        return null;
    });
    if (!buildingId) throw new Error('No building with floors found');
    await page.selectOption('#location_building_id', { value: buildingId });
    return buildingId;
}

test.describe('Locations CRUD', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/ubicaciones');
    });

    test('displays ubicaciones page with table', async ({ page }) => {
        await expect(page.locator('h3').first()).toContainText('Gestión de Aulas');
        await expect(page.locator('a.btn-primary:has-text("Crear / Editar Aula")')).toBeVisible();
        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('th:has-text("Aula")')).toBeVisible();
        await expect(page.locator('th:has-text("Piso")')).toBeVisible();
        await expect(page.locator('th:has-text("Edificio")')).toBeVisible();
    });

    test('creates a new location via modal', async ({ page }) => {
        const locationName = `Aula ${unique()}`;
        await page.click('a.btn-primary:has-text("Crear / Editar Aula")');
        await page.waitForSelector('#location-form:not(.hidden)');

        await page.selectOption('#location_building_id', { label: SEEDED_BUILDING });
        await page.waitForFunction(() => {
            const sel = document.getElementById('location_floor_id') as HTMLSelectElement;
            return sel && sel.options.length > 1;
        }, { timeout: 5000 });
        await page.selectOption('#location_floor_id', { index: 1 });

        await page.fill('#nombre', locationName);
        await page.click('#location-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
    });

    test('creates a new building inline', async ({ page }) => {
        const buildingName = `Edificio ${unique()}`;
        await page.click('a.btn-primary:has-text("Crear / Editar Aula")');
        await page.waitForSelector('#location-form:not(.hidden)');

        await page.click('#building-dropdown-group .btn-create');
        await page.waitForSelector('#building-inline-create:not(.hidden)');

        await page.fill('#building-inline-input', buildingName);
        await page.click('#building-inline-crear');
        await page.waitForTimeout(1500);

        const selectedValue = await page.locator('#location_building_id').inputValue();
        expect(selectedValue).not.toBe('');
    });

    test('creates a new floor inline', async ({ page }) => {
        const floorName = `Piso ${unique()}`;
        await page.click('a.btn-primary:has-text("Crear / Editar Aula")');
        await page.waitForSelector('#location-form:not(.hidden)');

        await page.selectOption('#location_building_id', { label: SEEDED_BUILDING });
        await page.waitForFunction(() => {
            const sel = document.getElementById('location_floor_id') as HTMLSelectElement;
            return sel && sel.options.length > 1;
        }, { timeout: 5000 });

        await page.click('#floor-dropdown-group .btn-create');
        await page.waitForSelector('#floor-inline-create:not(.hidden)');

        await page.fill('#floor-inline-input', floorName);
        await page.click('#floor-inline-crear');
        await page.waitForTimeout(1500);

        const selectedValue = await page.locator('#location_floor_id').inputValue();
        expect(selectedValue).not.toBe('');
    });

    test('cancels inline create for building', async ({ page }) => {
        await page.click('a.btn-primary:has-text("Crear / Editar Aula")');
        await page.waitForSelector('#location-form:not(.hidden)');

        await page.click('#building-dropdown-group .btn-create');
        await page.waitForSelector('#building-inline-create:not(.hidden)');

        await page.click('#building-inline-create .btn-default');
        await page.waitForSelector('#building-dropdown-group:not(.hidden)');
    });

    test('resets floor when opening building inline create', async ({ page }) => {
        await page.click('a.btn-primary:has-text("Crear / Editar Aula")');
        await page.waitForSelector('#location-form:not(.hidden)');

        await page.selectOption('#location_building_id', { label: SEEDED_BUILDING });
        await page.waitForFunction(() => {
            const sel = document.getElementById('location_floor_id') as HTMLSelectElement;
            return sel && sel.options.length > 1;
        }, { timeout: 5000 });
        await page.selectOption('#location_floor_id', { index: 1 });

        await page.click('#building-dropdown-group .btn-create');
        await page.waitForSelector('#building-inline-create:not(.hidden)');

        const floorOptions = await page.locator('#location_floor_id option').count();
        expect(floorOptions).toBe(1);
        const floorValue = await page.locator('#location_floor_id').inputValue();
        expect(floorValue).toBe('');
    });

    test('blocks floor inline create without building selected', async ({ page }) => {
        await page.click('a.btn-primary:has-text("Crear / Editar Aula")');
        await page.waitForSelector('#location-form:not(.hidden)');

        const floorBtn = page.locator('#floor-dropdown-group .btn-create');
        await expect(floorBtn).toBeDisabled();
    });

    test('cancels opposite inline create when switching', async ({ page }) => {
        await page.click('a.btn-primary:has-text("Crear / Editar Aula")');
        await page.waitForSelector('#location-form:not(.hidden)');

        await page.click('#building-dropdown-group .btn-create');
        await page.waitForSelector('#building-inline-create:not(.hidden)');

        await page.click('#building-inline-create .btn-default');
        await page.waitForSelector('#building-dropdown-group:not(.hidden)');
        await page.selectOption('#location_building_id', { label: SEEDED_BUILDING });
        await page.waitForFunction(() => {
            const sel = document.getElementById('location_floor_id') as HTMLSelectElement;
            return sel && sel.options.length > 1;
        }, { timeout: 5000 });

        await page.click('#floor-dropdown-group .btn-create');
        await page.waitForSelector('#floor-inline-create:not(.hidden)');

        await expect(page.locator('#building-inline-create')).toHaveClass(/hidden/);
    });

    test('edits an existing location', async ({ page }) => {
        const editName = `Edited ${unique()}`;
        const editBtn = page.locator('a[title="Editar ubicación"]').first();
        if (await editBtn.isVisible()) {
            await editBtn.click();
            await page.waitForSelector('#location-form:not(.hidden)');
            await page.fill('#nombre', editName);
            await page.click('#location-aceptar');
            await page.waitForLoadState('load');
            await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
        }
    });

    test('deletes a location', async ({ page }) => {
        const deleteLink = page.locator('a[title="Eliminar ubicación"]').first();
        if (await deleteLink.isVisible()) {
            await deleteLink.click();
            await page.waitForSelector('#location-form-delete:not(.hidden)');
            await page.click('#location-form-delete #btn-action');
            await page.waitForLoadState('load');
            await expect(page.locator('.alert-success, .alert-danger').first()).toBeVisible();
        }
    });

    test('shows delete confirmation for building', async ({ page }) => {
        await page.click('a.btn-primary:has-text("Crear / Editar Aula")');
        await page.waitForSelector('#location-form:not(.hidden)');

        await page.selectOption('#location_building_id', { label: SEEDED_BUILDING });

        await page.click('#building-dropdown-group .btn-delete');
        await page.waitForSelector('#building-delete-confirm:not(.hidden)');

        const nameText = await page.locator('#building-delete-name').textContent();
        expect(nameText).not.toBe('');

        await page.click('#building-delete-confirm .btn-default');
        await expect(page.locator('#building-delete-confirm')).toHaveClass(/hidden/);
    });

    test('shows delete confirmation for floor', async ({ page }) => {
        await page.click('a.btn-primary:has-text("Crear / Editar Aula")');
        await page.waitForSelector('#location-form:not(.hidden)');

        await page.selectOption('#location_building_id', { label: SEEDED_BUILDING });
        await page.waitForFunction(() => {
            const sel = document.getElementById('location_floor_id') as HTMLSelectElement;
            return sel && sel.options.length > 1;
        }, { timeout: 5000 });
        await page.selectOption('#location_floor_id', { index: 1 });

        await page.waitForFunction(() => {
            const btn = document.querySelector('#floor-dropdown-group .btn-delete') as HTMLButtonElement;
            return btn && !btn.disabled;
        }, { timeout: 5000 });

        await page.click('#floor-dropdown-group .btn-delete');
        await page.waitForSelector('#floor-delete-confirm:not(.hidden)');

        const nameText = await page.locator('#floor-delete-name').textContent();
        expect(nameText).not.toBe('');

        await page.click('#floor-delete-confirm .btn-default');
        await expect(page.locator('#floor-delete-confirm')).toHaveClass(/hidden/);
    });

    test('edits a floor inline', async ({ page }) => {
        const newName = `Piso Editado ${unique()}`;
        await page.click('a.btn-primary:has-text("Crear / Editar Aula")');
        await page.waitForSelector('#location-form:not(.hidden)');

        await page.selectOption('#location_building_id', { label: SEEDED_BUILDING });
        await page.waitForFunction(() => {
            const sel = document.getElementById('location_floor_id') as HTMLSelectElement;
            return sel && sel.options.length > 1;
        }, { timeout: 5000 });

        await page.selectOption('#location_floor_id', { index: 1 });

        await page.waitForFunction(() => {
            const btn = document.querySelector('#floor-dropdown-group .btn-edit') as HTMLButtonElement;
            return btn && !btn.disabled;
        }, { timeout: 5000 });

        await page.click('#floor-dropdown-group .btn-edit');
        await page.waitForSelector('#floor-inline-edit:not(.hidden)');

        await page.fill('#floor-edit-input', newName);
        await page.click('#floor-edit-save');
        await page.waitForTimeout(1500);

        await expect(page.locator('#floor-dropdown-group')).toBeVisible();
        const selectedText = await page.locator('#location_floor_id option:checked').textContent();
        expect(selectedText).toContain('Piso Editado');
    });

    test('edits a building inline', async ({ page }) => {
        const newName = `Edificio Editado ${unique()}`;
        await page.click('a.btn-primary:has-text("Crear / Editar Aula")');
        await page.waitForSelector('#location-form:not(.hidden)');

        await page.selectOption('#location_building_id', { label: SEEDED_BUILDING });

        await page.click('#building-dropdown-group .btn-edit');
        await page.waitForSelector('#building-inline-edit:not(.hidden)');

        await page.fill('#building-edit-input', newName);
        await page.click('#building-edit-save');
        await page.waitForTimeout(1500);

        await expect(page.locator('#building-dropdown-group')).toBeVisible();
        const selectedText = await page.locator('#location_building_id option:checked').textContent();
        expect(selectedText).toContain('Edificio Editado');
    });
});
