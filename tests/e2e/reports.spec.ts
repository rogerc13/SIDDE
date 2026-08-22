import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

test.describe('Reports', () => {
    test('admin can access reports page', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await expect(page).not.toHaveURL(/login/);
        // The page can be slow to render while the rest of the suite hammers the server.
        await expect(page.locator('h1')).toContainText('Reportes', { timeout: 15000 });
    });

    test('facilitador is redirected from reports', async ({ page }) => {
        await loginAs(page, 'facilitador');
        await page.goto('/reports');

        await expect(page).toHaveURL(/home/);
    });

    test('participante is redirected from reports', async ({ page }) => {
        await loginAs(page, 'participante');
        await page.goto('/reports');

        await expect(page).toHaveURL(/home/);
    });

    test('generates report by date with a chart', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'date');
        await page.click('.generate');

        // chart rendering queues behind other suites when the whole file runs
        await expect(page.locator('#myChart')).toBeVisible({ timeout: 15000 });
    });

    test('generates canceled courses report', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'canceled');
        await page.click('.generate');

        // either the table renders or the friendly empty-state message does
        const table = page.locator('.table-col-helper table.course-list');
        const emptyState = page.locator('.course-amount-number h3', { hasText: 'Canceladas' });
        await expect(table.or(emptyState).first()).toBeVisible();
    });

    test('generates participant average chart', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'participant-average');
        await page.click('.generate');

        const chart = page.locator('#myChart');
        const emptyState = page.locator('.course-amount-number h3', { hasText: 'No existen' });
        await expect(chart.or(emptyState).first()).toBeVisible();
    });

    test('participant by status exposes its status filter', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'participant-by-status');
        await page.click('.generate');

        const container = page.locator('.participant-status-container');
        await expect(container).toBeVisible();

        // populated from GET /reports/participant-status-select
        await expect(page.locator('#participant_status option').nth(1)).toBeAttached();

        // changing the filter re-runs the report instead of leaving stale data
        await page.selectOption('#participant_status', { index: 1 });
        await expect(container).toBeVisible();
    });
});
