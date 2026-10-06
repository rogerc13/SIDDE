import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { loginAs } from './helpers/auth';

test.describe('Home dashboard', () => {
    test('admin sees the overview dashboard', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/home');

        await expect(page.locator('.sidde-kpi')).toHaveCount(4);
        await expect(page.locator('a.sidde-kpi')).toHaveCount(4);
        await expect(page.locator('.sidde-kpi-unit').first()).toBeVisible();
        await expect(page.locator('.sidde-panel-title', { hasText: 'Facilitadores activos' })).toBeVisible();
        await expect(page.locator('.sidde-panel-title', { hasText: 'Participantes activos' })).toBeVisible();
        await expect(page.locator('.sidde-panel-title', { hasText: 'Programas por estado' })).toBeVisible();
        await expect(page.locator('.sidde-rail-list .badge').first()).toBeVisible();
        await expect(
            page.locator('.sidde-panel-title', { hasText: 'Próximas Acciones de Formación' }),
        ).toBeVisible();
        await expect(page.locator('.sidde-panel-link', { hasText: 'Ver todas' }).first()).toBeVisible();
        await expect(page.locator('.hd-card-title')).toHaveCount(0);
    });

    test('facilitator sees a personalized agenda', async ({ page }) => {
        await loginAs(page, 'facilitador');
        await page.goto('/home');

        await expect(page.locator('.sidde-panel-title', { hasText: 'Mis próximas acciones de formación' })).toBeVisible();
        await expect(page.locator('.sidde-panel-title', { hasText: 'Facilitadores activos' })).toHaveCount(0);
        await expect(page.locator('.sidde-kpi-strip .sidde-kpi')).toHaveCount(3);
        await expect(page.locator('.sidde-panel-link', { hasText: /Ver todas \(\d+\)/ })).toBeVisible();
    });

    test('participant sees own enrolments', async ({ page }) => {
        await loginAs(page, 'participante');
        await page.goto('/home');

        await expect(page.locator('.sidde-panel-title', { hasText: 'Mis acciones de formación' }).first()).toBeVisible();
        await expect(page.locator('.sidde-panel-title', { hasText: 'Participantes activos' })).toHaveCount(0);
        await expect(page.locator('.sidde-panel-link', { hasText: /Ver todas \(\d+\)/ })).toBeVisible();
        await expect(page.locator('.sidde-table tbody .btn').first()).toBeVisible();
    });

    test('the navigation menu can be opened at phone widths', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.setViewportSize({ width: 375, height: 812 });
        await page.goto('/home');

        const menu = page.locator('#main-menu');
        await expect(menu).toBeHidden();

        await page.locator('.sidebar-mobile-menu a').click();

        await expect(menu).toBeVisible();
        await expect(menu.locator('li a').first()).toBeVisible();
    });

    test('the sidebar spans the full width at tablet widths', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.setViewportSize({ width: 991, height: 1000 });
        await page.goto('/home');

        const sidebar = page.locator('.sidebar-menu');
        const box = await sidebar.boundingBox();

        expect(box).not.toBeNull();
        expect(Math.round(box!.width)).toBe(991);
        await expect(sidebar).toHaveCSS('display', 'block');
    });

    test('agenda keeps status, dates and actions at phone widths', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.setViewportSize({ width: 375, height: 812 });
        await page.goto('/home');

        const firstRow = page.locator('.sidde-agenda-row').first();
        await expect(firstRow.locator('.sidde-status')).toBeVisible();
        await expect(firstRow.locator('.sidde-agenda-meta')).toBeVisible();
        await expect(firstRow.locator('.sidde-action a')).toBeVisible();

        const firstKpi = await page.locator('.sidde-dashboard-kpis .sidde-kpi').nth(0).boundingBox();
        const secondKpi = await page.locator('.sidde-dashboard-kpis .sidde-kpi').nth(1).boundingBox();

        expect(firstKpi).not.toBeNull();
        expect(secondKpi).not.toBeNull();
        expect(secondKpi!.y).toBeGreaterThan(firstKpi!.y);
    });

    test('overview dashboard has no serious accessibility violations', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/home');
        // The Neon page-fade transition keeps a dark body background for ~850ms,
        // which would make axe sample colours mid-animation.
        await page.waitForFunction(() => !document.body.classList.contains('page-fade-only'));

        const results = await new AxeBuilder({ page })
            .include('.home-dashboard')
            .analyze();

        const serious = results.violations.filter((violation) =>
            ['serious', 'critical'].includes(violation.impact ?? ''),
        );

        expect(serious).toEqual([]);
    });
});
