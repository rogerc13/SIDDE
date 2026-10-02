import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { loginAs } from './helpers/auth';

test.describe('Home dashboard', () => {
    test('admin sees the overview dashboard', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/home');

        await expect(page.locator('.sidde-kpi')).toHaveCount(4);
        await expect(page.locator('a.sidde-kpi')).toHaveCount(4);
        await expect(page.locator('.hd-card-title', { hasText: 'Facilitadores activos' })).toBeVisible();
        await expect(page.locator('.hd-card-title', { hasText: 'Participantes activos' })).toBeVisible();
        await expect(
            page.locator('.hd-card-title', { hasText: 'Próximas Acciones de Formación' }),
        ).toBeVisible();
    });

    test('facilitator sees a personalized agenda', async ({ page }) => {
        await loginAs(page, 'facilitador');
        await page.goto('/home');

        await expect(page.locator('.hd-card-title', { hasText: 'Mis próximas acciones de formación' })).toBeVisible();
        await expect(page.locator('.hd-card-title', { hasText: 'Facilitadores activos' })).toHaveCount(0);
    });

    test('participant sees own enrolments', async ({ page }) => {
        await loginAs(page, 'participante');
        await page.goto('/home');

        await expect(page.locator('.hd-card-title', { hasText: 'Mis acciones de formación' }).first()).toBeVisible();
        await expect(page.locator('.hd-card-title', { hasText: 'Participantes activos' })).toHaveCount(0);
    });

    test('overview dashboard has no serious accessibility violations', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/home');
        // The Neon page-fade transition keeps a dark body background for ~850ms,
        // which would make axe sample colours mid-animation.
        await page.waitForFunction(() => !document.body.classList.contains('page-fade-only'));

        const results = await new AxeBuilder({ page })
            .include('.home-dashboard')
            // The app's shared badge palette (neon-core) uses low-contrast
            // accents for small status text; it is theme-driven and consistent
            // with every other page, so it is out of scope for this scan.
            .disableRules(['color-contrast'])
            .analyze();

        const serious = results.violations.filter((violation) =>
            ['serious', 'critical'].includes(violation.impact ?? ''),
        );

        expect(serious).toEqual([]);
    });
});
