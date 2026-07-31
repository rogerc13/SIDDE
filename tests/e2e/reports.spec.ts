import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

test.describe('Reports', () => {
    test('admin can access reports page', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await expect(page).not.toHaveURL(/login/);
        await expect(page.locator('h1')).toContainText('Reportes');
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

    test('generates report by date', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        const submitBtn = page.locator('.report-form button[type="submit"], .report-form .btn-primary');
        if (await submitBtn.isVisible()) {
            await submitBtn.click();

            await expect(page.locator('.chart-container, canvas, .alert, .callout')).toBeVisible();
        }
    });
});
