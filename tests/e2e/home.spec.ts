import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { loginAs } from './helpers/auth';

test.describe('Home dashboard', () => {
    test('admin sees the overview dashboard', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/home');

        await expect(page.locator('.sidde-kpi')).toHaveCount(3);
        await expect(page.locator('a.sidde-kpi')).toHaveCount(3);
        await expect(page.locator('.sidde-kpi-unit').first()).toBeVisible();
        await expect(page.locator('.sidde-kpi-label', { hasText: 'Facilitadores activos' })).toBeVisible();
        await expect(page.locator('.sidde-panel-title', { hasText: 'Acciones de Formación Programadas por estado' })).toBeVisible();
        await expect(page.locator('.sidde-kpi--facilitators .sidde-kpi-person-meta').first()).toBeVisible();
        await expect(page.locator('a.sidde-kpi--facilitators')).toHaveAttribute('href', /\/u\/facilitadores$/);

        const singleKpi = await page.locator('.sidde-kpi:not(.sidde-kpi--facilitators)').first().boundingBox();
        const facilitatorKpi = await page.locator('.sidde-kpi--facilitators').boundingBox();
        expect(singleKpi).not.toBeNull();
        expect(facilitatorKpi).not.toBeNull();
        expect(facilitatorKpi!.width).toBeGreaterThan(singleKpi!.width);

        const singleIcon = await page.locator('.sidde-kpi:not(.sidde-kpi--facilitators) .kpi-icon').first().boundingBox();
        const facilitatorIcon = await page.locator('.sidde-kpi--facilitators .kpi-icon').boundingBox();
        expect(singleIcon).not.toBeNull();
        expect(facilitatorIcon).not.toBeNull();
        expect(
            Math.abs(
                (facilitatorIcon!.y + facilitatorIcon!.height / 2) -
                    (singleIcon!.y + singleIcon!.height / 2),
            ),
        ).toBeLessThanOrEqual(1);
        await expect(
            page.locator('.sidde-panel-title', { hasText: 'Próximas Acciones de Formación' }),
        ).toBeVisible();
        await expect(
            page.locator('.sidde-panel-title', { hasText: 'Próximas Clases' }),
        ).toBeVisible();
        await expect(page.locator('.sidde-dashboard-primary #upcoming-sessions-heading')).toBeVisible();
        await expect(page.locator('.sidde-dashboard-rail #upcoming-heading')).toBeVisible();
        await expect(page.locator('.sidde-condensed-scroll')).toHaveCSS('overflow-y', 'auto');

        const railPanels = page.locator('.sidde-dashboard-rail .sidde-panel');
        const scheduledPanelBox = await railPanels.nth(0).boundingBox();
        const statusPanelBox = await railPanels.nth(1).boundingBox();
        expect(scheduledPanelBox).not.toBeNull();
        expect(statusPanelBox).not.toBeNull();
        expect(scheduledPanelBox!.y).toBeLessThan(statusPanelBox!.y);

        await expect(page.locator('.sidde-header-actions .sidde-quick-btn').first()).toBeVisible();
        await expect(page.locator('.sidde-status-stack')).toBeVisible();
        await expect(page.locator('.sidde-panel-link--icon').first()).toBeVisible();
        await expect(page.locator('.hd-card-title')).toHaveCount(0);
    });

    test('sidebar shows an Inicio link above Áreas de Conocimiento', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/home');

        const inicio = page.locator('#main-menu a', { hasText: 'Inicio' });
        const areas = page.locator('#main-menu a', { hasText: 'Áreas de Conocimiento' });

        await expect(inicio).toBeVisible();
        await expect(inicio).toHaveAttribute('href', /\/home$/);
        await expect(areas).toBeVisible();
        expect(await inicio.boundingBox()).not.toBeNull();
        expect(await areas.boundingBox()).not.toBeNull();
        expect((await inicio.boundingBox())!.y).toBeLessThan((await areas.boundingBox())!.y);
    });

    test('facilitator sees a personalized calendar', async ({ page }) => {
        await loginAs(page, 'facilitador');
        await page.goto('/home');

        await expect(page.locator('.sidde-panel-title', { hasText: 'Calendario de acciones de formación' })).toBeVisible();
        await expect(page.locator('.sidde-kpi-label', { hasText: 'Facilitadores activos' })).toHaveCount(0);
        await expect(page.locator('.sidde-kpi-strip .sidde-kpi')).toHaveCount(3);
        await expect(page.locator('.sidde-kpi-strip .sidde-kpi-label')).toHaveText(['Programadas', 'Por dictar', 'Culminadas']);
        await expect(page.locator('#home-calendar .fc-view')).toBeVisible();
        await expect(page.locator('.sidde-header-actions .sidde-quick-btn').first()).toBeVisible();
        await expect(page.locator('a.sidde-panel-link--icon[aria-label^="Ver todas mis acciones"]')).toBeVisible();
    });

    test('participant sees own enrolments in the calendar', async ({ page }) => {
        await loginAs(page, 'participante');
        await page.goto('/home');

        await expect(page.locator('.sidde-panel-title', { hasText: 'Calendario de acciones de formación' }).first()).toBeVisible();
        await expect(page.locator('.sidde-kpi-strip .sidde-kpi')).toHaveCount(3);
        await expect(page.locator('.sidde-kpi-strip .sidde-kpi-label')).toHaveText(['Programadas', 'Por dictar', 'Culminadas']);
        await expect(page.locator('#home-calendar .fc-view')).toBeVisible();
        await expect(page.locator('.sidde-header-actions .sidde-quick-btn').first()).toBeVisible();
        await expect(page.locator('a.sidde-panel-link--icon[aria-label^="Ver todas mis acciones"]')).toBeVisible();
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
        const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);

        expect(box).not.toBeNull();
        expect(Math.round(box!.width)).toBe(clientWidth);
        await expect(sidebar).toHaveCSS('display', 'block');
    });

    test('condensed upcoming actions stay readable at phone widths', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.setViewportSize({ width: 375, height: 812 });
        await page.goto('/home');

        const firstRow = page.locator('.sidde-condensed-item').first();
        await expect(firstRow.locator('.sidde-agenda-date')).toBeVisible();
        await expect(firstRow.locator('.sidde-agenda-course-title')).toBeVisible();
        await expect(firstRow.locator('.sidde-status')).toBeVisible();

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

        // Accepted exceptions: status pills use the admin index's solid Neon
        // palette with white labels (matching /u/af_programadas), and the
        // primary "Programar A.F." CTA and the facilitator session count pill
        // reuse the index's brand green (#00a651). All sit below the AA 4.5:1
        // threshold by product decision, so we drop only those contrast nodes
        // and still assert everything else on the dashboard is clean.
        const CONTRAST_EXCEPTIONS = ['.sidde-quick-btn--primary', '.badge', '.count-pill'];

        const unexpected = serious
            .map((violation) =>
                violation.id === 'color-contrast'
                    ? {
                          ...violation,
                          nodes: violation.nodes.filter(
                              (node) =>
                                  !node.target.some((target) =>
                                      CONTRAST_EXCEPTIONS.some((selector) =>
                                          String(target).includes(selector),
                                      ),
                                  ),
                          ),
                      }
                    : violation,
            )
            .filter((violation) => violation.nodes.length > 0);

        expect(unexpected).toEqual([]);
    });
});
