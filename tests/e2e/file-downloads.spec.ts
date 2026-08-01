import { test, expect } from '@playwright/test';
import { loginAs } from './helpers/auth';

const COURSE_ID = 1;

test.describe('File Download Restrictions', () => {

    test('unauthenticated user is redirected to login', async ({ page }) => {
        await page.goto(`/download/${COURSE_ID}/2`);
        await expect(page).toHaveURL(/login/);
    });

    test('participant can view ficha tecnica page', async ({ page }) => {
        await loginAs(page, 'participante');
        await page.goto(`/acciones_formacion/${COURSE_ID}`);
        await expect(page.locator('h3, .ficha-course-title').first()).toBeVisible();
    });

    test('participant sees only Manual de Participante link in ficha', async ({ page }) => {
        await loginAs(page, 'participante');
        await page.goto(`/acciones_formacion/${COURSE_ID}`);
        await page.click('button.course-files');
        await page.waitForTimeout(500);

        const manualP = page.locator('a:has-text("Manual de Participante")').first();
        await expect(manualP).toBeVisible();

        const manualF = page.locator('a:has-text("Manual de Facilitador")');
        await expect(manualF).toHaveCount(0);

        const guia = page.locator('a:has-text("Guia")');
        await expect(guia).toHaveCount(0);

        const presentacion = page.locator('a:has-text("Presentacion")');
        await expect(presentacion).toHaveCount(0);
    });

    test('admin sees all four download links in ficha', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto(`/acciones_formacion/${COURSE_ID}`);
        await page.click('button.course-files');
        await page.waitForTimeout(500);

        await expect(page.locator('a:has-text("Manual de Facilitador")').first()).toBeVisible();
        await expect(page.locator('a:has-text("Manual de Participante")').first()).toBeVisible();
        await expect(page.locator('a:has-text("Guia")').first()).toBeVisible();
        await expect(page.locator('a:has-text("Presentacion")').first()).toBeVisible();
    });

    test('participant gets 403 when accessing Manual de Facilitador', async ({ page }) => {
        await loginAs(page, 'participante');
        const response = await page.goto(`/download/${COURSE_ID}/1`);
        expect(response?.status()).toBe(403);
    });

    test('participant gets 403 when accessing Guia', async ({ page }) => {
        await loginAs(page, 'participante');
        const response = await page.goto(`/download/${COURSE_ID}/3`);
        expect(response?.status()).toBe(403);
    });

    test('participant gets 403 when accessing Presentacion', async ({ page }) => {
        await loginAs(page, 'participante');
        const response = await page.goto(`/download/${COURSE_ID}/4`);
        expect(response?.status()).toBe(403);
    });

    test('participant can access Manual de Participante endpoint', async ({ page }) => {
        await loginAs(page, 'participante');
        const response = await page.goto(`/download/${COURSE_ID}/2`);
        expect(response?.status()).not.toBe(403);
    });

    test('admin can access all file types', async ({ page }) => {
        await loginAs(page, 'admin');
        for (const type of [1, 2, 3, 4]) {
            const response = await page.goto(`/download/${COURSE_ID}/${type}`);
            expect(response?.status()).not.toBe(403);
        }
    });

    test('participant download button visible in miscursos', async ({ page }) => {
        await loginAs(page, 'participante');
        await page.goto('/u/mis_acciones');
        const downloadBtn = page.locator('a .entypo-download').first();
        await expect(downloadBtn).toBeVisible();
    });

    test('participant document modal shows only Manual de Participante', async ({ page }) => {
        await loginAs(page, 'participante');
        await page.goto('/u/mis_acciones');

        const downloadBtn = page.locator('a:has(.entypo-download)').first();
        if (await downloadBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
            const isDisabled = await downloadBtn.evaluate(el => el.classList.contains('disabled'));
            if (!isDisabled) {
                await downloadBtn.click();
                await page.waitForSelector('#document-modal.in', { timeout: 5000 });

                const modalBody = page.locator('#document-modal .modal-body');
                await expect(modalBody).toContainText('Manual de Participante');
                await expect(modalBody).not.toContainText('Manual de Facilitador');
            }
        }
    });
});
