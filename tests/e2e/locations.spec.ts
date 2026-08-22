import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

const unique = () => Date.now();

type Fixtures = { buildingId: number; floorId: number; buildingName: string; floorName: string };

// Structures created ad-hoc by individual tests (inline creates) that must be
// removed before the fixture parents can be deleted.
let extras: { locationIds: number[]; floorIds: number[]; buildingIds: number[] };
let fixtures: Fixtures;

// Soft-deleted buildings keep occupying their name (unique:buildings,name),
// so every test gets freshly-named fixtures.
function fixtureNames(): { buildingName: string; floorName: string } {
    const id = `${Date.now()}-${Math.floor(Math.random() * 100000)}`;
    return {
        buildingName: `Edificio E2E ${id}`,
        floorName: `Piso E2E ${id}`,
    };
}

async function apiHeaders(page: import('@playwright/test').Page): Promise<Record<string, string>> {
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    if (!token) throw new Error('CSRF token not found on page');
    return {
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        // Controllers answer non-JSON writes with Redirect::back(); without a
        // Referer header that becomes a redirect loop for API requests.
        'Referer': page.url(),
    };
}

async function createFixtures(page: import('@playwright/test').Page): Promise<Fixtures> {
    const headers = await apiHeaders(page);
    const { buildingName, floorName } = fixtureNames();

    const buildingRes = await page.request.post('/u/ubicaciones/building', {
        headers,
        form: { name: buildingName },
    });
    if (!buildingRes.ok()) throw new Error(`Failed to create fixture building: ${buildingRes.status()}`);
    const building = await buildingRes.json();

    const floorRes = await page.request.post('/u/ubicaciones/floor', {
        headers,
        form: { name: floorName, building_id: building.id },
    });
    if (!floorRes.ok()) throw new Error(`Failed to create fixture floor: ${floorRes.status()}`);
    const floor = await floorRes.json();

    return { buildingId: building.id, floorId: floor.id, buildingName, floorName };
}

async function createLocation(page: import('@playwright/test').Page, name: string): Promise<void> {
    await page.request.post('/u/ubicaciones', {
        headers: await apiHeaders(page),
        form: { nombre: name, floor_id: fixtures.floorId },
    });
}

test.describe('Locations CRUD', () => {
    test.beforeEach(async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/u/ubicaciones');
        fixtures = await createFixtures(page);
        extras = { locationIds: [], floorIds: [], buildingIds: [] };

        // The modal's building dropdown is rendered server-side; reload so it includes the fixtures.
        await page.reload();
    });

    test.afterEach(async ({ page }) => {
        // Best-effort teardown; children must be deleted before parents
        // (floors refuse deletion while they still have aulas assigned).
        //
        // Deletes are sent as method-spoofed POSTs (_method=DELETE), mirroring
        // how the app's own modals submit deletions.
        const headers = await apiHeaders(page);

        const safeDelete = async (path: string): Promise<void> => {
            try {
                const res = await page.request.post(path, { headers, form: { _method: 'DELETE' } });
                if (!res.ok()) {
                    const body = await res.text();
                    const allow = res.headers()['allow'] ?? '-';
                    console.log(`[teardown] ${path}: ${res.status()} allow=${allow} body=${body.slice(0, 200)}`);
                }
            } catch (error) {
                console.log(`[teardown] ${path}:`, error);
            }
        };

        // Extra structures created by inline-create tests.
        for (const id of extras.locationIds) await safeDelete(`/u/ubicaciones/${id}`);
        for (const floorId of extras.floorIds) await safeDelete(`/u/ubicaciones/floor/${floorId}`);
        for (const buildingId of extras.buildingIds) await safeDelete(`/u/ubicaciones/building/${buildingId}`);

        // Locations created on the fixture floor (via modal or API).
        try {
            const res = await page.request.get('/u/ubicaciones/select');
            const locations = (await res.json()) as Array<{ id: number; floor_id?: number }>;
            for (const location of locations) {
                if (location.floor_id === fixtures.floorId) {
                    await safeDelete(`/u/ubicaciones/${location.id}`);
                }
            }
        } catch (error) {
            console.log('[teardown] select:', error);
        }

        await safeDelete(`/u/ubicaciones/floor/${fixtures.floorId}`);
        await safeDelete(`/u/ubicaciones/building/${fixtures.buildingId}`);
    });

    async function openLocationModal(page: import('@playwright/test').Page): Promise<void> {
        await page.click('a.btn-primary:has-text("Crear / Editar Aula")');
        await page.waitForSelector('#location-form:not(.hidden)');
    }

    async function selectFixtureBuilding(page: import('@playwright/test').Page): Promise<void> {
        await page.selectOption('#location_building_id', { label: fixtures.buildingName });
        await page.waitForFunction(() => {
            const sel = document.getElementById('location_floor_id') as HTMLSelectElement;
            return sel && sel.options.length > 1;
        }, { timeout: 5000 });
    }

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
        await openLocationModal(page);

        await selectFixtureBuilding(page);
        await page.selectOption('#location_floor_id', { label: fixtures.floorName });

        await page.fill('#nombre', locationName);
        await page.click('#location-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success').first()).toBeVisible();
        await expect(page.locator('tr', { hasText: locationName })).toBeVisible();
    });

    test('creates a new building inline', async ({ page }) => {
        const buildingName = `Edificio ${unique()}`;
        await openLocationModal(page);

        await page.click('#building-dropdown-group .btn-create');
        await page.waitForSelector('#building-inline-create:not(.hidden)');

        await page.fill('#building-inline-input', buildingName);
        await page.click('#building-inline-crear');
        await page.waitForTimeout(1500);

        const selectedValue = await page.locator('#location_building_id').inputValue();
        expect(selectedValue).not.toBe('');
        extras.buildingIds.push(Number(selectedValue));
    });

    test('creates a new floor inline', async ({ page }) => {
        const floorName = `Piso ${unique()}`;
        await openLocationModal(page);
        await selectFixtureBuilding(page);

        await page.click('#floor-dropdown-group .btn-create');
        await page.waitForSelector('#floor-inline-create:not(.hidden)');

        await page.fill('#floor-inline-input', floorName);
        await page.click('#floor-inline-crear');
        await page.waitForTimeout(1500);

        const selectedValue = await page.locator('#location_floor_id').inputValue();
        expect(selectedValue).not.toBe('');
        extras.floorIds.push(Number(selectedValue));
    });

    test('cancels inline create for building', async ({ page }) => {
        await openLocationModal(page);

        await page.click('#building-dropdown-group .btn-create');
        await page.waitForSelector('#building-inline-create:not(.hidden)');

        await page.click('#building-inline-create .btn-default');
        await page.waitForSelector('#building-dropdown-group:not(.hidden)');
    });

    test('resets floor when opening building inline create', async ({ page }) => {
        await openLocationModal(page);
        await selectFixtureBuilding(page);
        await page.selectOption('#location_floor_id', { index: 1 });

        await page.click('#building-dropdown-group .btn-create');
        await page.waitForSelector('#building-inline-create:not(.hidden)');

        const floorOptions = await page.locator('#location_floor_id option').count();
        expect(floorOptions).toBe(1);
        const floorValue = await page.locator('#location_floor_id').inputValue();
        expect(floorValue).toBe('');
    });

    test('blocks floor inline create without building selected', async ({ page }) => {
        await openLocationModal(page);

        const floorBtn = page.locator('#floor-dropdown-group .btn-create');
        await expect(floorBtn).toBeDisabled();
    });

    test('cancels opposite inline create when switching', async ({ page }) => {
        await openLocationModal(page);

        await page.click('#building-dropdown-group .btn-create');
        await page.waitForSelector('#building-inline-create:not(.hidden)');

        await page.click('#building-inline-create .btn-default');
        await page.waitForSelector('#building-dropdown-group:not(.hidden)');
        await selectFixtureBuilding(page);

        await page.click('#floor-dropdown-group .btn-create');
        await page.waitForSelector('#floor-inline-create:not(.hidden)');

        await expect(page.locator('#building-inline-create')).toHaveClass(/hidden/);
    });

    test('edits an existing location', async ({ page }) => {
        const originalName = `Aula Editable ${unique()}`;
        const editName = `Edited ${unique()}`;
        await createLocation(page, originalName);
        await page.reload();

        const row = page.locator('tr', { hasText: originalName });
        await expect(row).toBeVisible();

        await row.locator('a[title="Editar ubicación"]').click();
        await page.waitForSelector('#location-form:not(.hidden)');
        await page.fill('#nombre', editName);
        await page.click('#location-aceptar');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success').first()).toBeVisible();
        await expect(page.locator('tr', { hasText: editName })).toBeVisible();
    });

    test('deletes a location', async ({ page }) => {
        const locationName = `Aula Borrable ${unique()}`;
        await createLocation(page, locationName);
        await page.reload();

        const row = page.locator('tr', { hasText: locationName });
        await expect(row).toBeVisible();

        await row.locator('a[title="Eliminar ubicación"]').click();
        await page.waitForSelector('#location-form-delete:not(.hidden)');
        await page.click('#location-form-delete #btn-action');
        await page.waitForLoadState('load');
        await expect(page.locator('.alert-success').first()).toBeVisible();
        await expect(page.locator('tr', { hasText: locationName })).toHaveCount(0);
    });

    test('shows delete confirmation for building', async ({ page }) => {
        await openLocationModal(page);
        await selectFixtureBuilding(page);

        await page.click('#building-dropdown-group .btn-delete');
        await page.waitForSelector('#building-delete-confirm:not(.hidden)');

        const nameText = await page.locator('#building-delete-name').textContent();
        expect(nameText).toContain(fixtures.buildingName);

        await page.click('#building-delete-confirm .btn-default');
        await expect(page.locator('#building-delete-confirm')).toHaveClass(/hidden/);
    });

    test('shows delete confirmation for floor', async ({ page }) => {
        await openLocationModal(page);
        await selectFixtureBuilding(page);
        await page.selectOption('#location_floor_id', { label: fixtures.floorName });

        await page.waitForFunction(() => {
            const btn = document.querySelector('#floor-dropdown-group .btn-delete') as HTMLButtonElement;
            return btn && !btn.disabled;
        }, { timeout: 5000 });

        await page.click('#floor-dropdown-group .btn-delete');
        await page.waitForSelector('#floor-delete-confirm:not(.hidden)');

        const nameText = await page.locator('#floor-delete-name').textContent();
        expect(nameText).toContain(fixtures.floorName);

        await page.click('#floor-delete-confirm .btn-default');
        await expect(page.locator('#floor-delete-confirm')).toHaveClass(/hidden/);
    });

    test('edits a floor inline', async ({ page }) => {
        const newName = `Piso Editado ${unique()}`;
        await openLocationModal(page);
        await selectFixtureBuilding(page);
        await page.selectOption('#location_floor_id', { label: fixtures.floorName });

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
        await openLocationModal(page);
        await selectFixtureBuilding(page);

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
