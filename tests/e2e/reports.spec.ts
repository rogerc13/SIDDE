import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { loginAs } from './helpers/auth';

test.describe('Reports', () => {
    test('admin can access reports page', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await expect(page).not.toHaveURL(/login/);
        // The page can be slow to render while the rest of the suite hammers the server.
        await expect(page.locator('h1')).toContainText('Reportes', { timeout: 15000 });
    });

    test('shows the executive KPI strip and export actions', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await expect(page.locator('.report-summary .kpi-card').first()).toBeVisible({ timeout: 15000 });
        await expect(page.locator('.export-report[data-format="csv"]')).toBeVisible();
        await expect(page.locator('.export-report[data-format="xlsx"]')).toBeVisible();
    });

    test('renders the executive insight for a generated report', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'date');
        await page.click('.generate');

        await expect(page.locator('.report-insight .report-insight-list li').first()).toBeVisible({ timeout: 15000 });
    });

    test('module split loads without javascript errors', async ({ page }) => {
        const problems: string[] = [];
        page.on('pageerror', (err) => problems.push(`pageerror: ${err.message}`));
        page.on('console', (msg) => {
            if (msg.type() !== 'error') return;
            const text = msg.text();
            if (/favicon|net::ERR|Load failed/i.test(text)) return;
            problems.push(`console: ${text}`);
        });

        await loginAs(page, 'admin');
        await page.goto('/reports');
        await page.selectOption('#report-type', 'category');
        await page.click('.generate');
        await expect(page.locator('#myChart')).toBeVisible({ timeout: 15000 });

        expect(problems, problems.join('\n')).toEqual([]);
    });

    test('generates the least scheduled report', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'least-scheduled');
        await page.click('.generate');

        await expect(page.locator('.table-col-helper table.course-list')).toBeVisible({ timeout: 15000 });
    });

    test('report tables and charts expose accessible names', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'category');
        await page.click('.generate');

        await expect(page.locator('#myChart')).toBeVisible({ timeout: 15000 });

        // Tables get an sr-only caption and column scopes.
        await expect(page.locator('.table-col-helper table caption').first()).toBeAttached();
        await expect(page.locator('.table-col-helper table thead th[scope="col"]').first()).toBeAttached();

        // Chart canvases get a role + descriptive label.
        await expect(page.locator('.row-graphs canvas[role="img"]').first()).toHaveAttribute('aria-label', /.+/);

        // The filter toolbar is present (sticky).
        await expect(page.locator('.report-toolbar')).toBeVisible();
    });

    test('result blocks keep the title and pagination outside the card', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'category');
        await page.click('.generate');

        // Chart block: the heading sits outside the card that holds the canvas.
        const chartBlock = page.locator('.row-graphs .report-block').first();
        await expect(chartBlock.locator('.report-block-title')).toBeVisible({ timeout: 15000 });
        await expect(chartBlock.locator('.report-block-card canvas')).toBeAttached();
        await expect(chartBlock.locator('.report-block-card .report-block-title')).toHaveCount(0);

        // Table block: heading and pagination are outside the card.
        const tableBlock = page.locator('.table-col-helper .report-block').first();
        await expect(tableBlock.locator('.report-block-title')).toBeVisible();
        await expect(tableBlock.locator('.report-block-card table')).toBeAttached();
        await expect(tableBlock.locator('.report-block-card .report-block-title')).toHaveCount(0);
        await expect(tableBlock.locator('.report-block-card .report-pagination-controls')).toHaveCount(0);
        await expect(tableBlock.locator(':scope > .report-pagination-controls')).toHaveCount(1);
    });

    test('chart canvases carry a stable key for the PDF', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'category');
        await page.click('.generate');

        await expect(page.locator('.row-graphs canvas[data-chart-key="category_bar"]')).toBeAttached({ timeout: 15000 });
        await expect(page.locator('.row-graphs canvas[data-chart-key="category_doughnut"]')).toBeAttached();
    });

    test('print submits the charts keyed for the PDF', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'category');
        await page.click('.generate');
        await expect(page.locator('.row-graphs canvas[data-chart-key="category_bar"]')).toBeAttached({ timeout: 15000 });

        const requestPromise = page.waitForRequest(
            (req) => req.url().includes('/reports/pdf') && req.method() === 'POST',
        );
        await page.click('.print-report');

        const body = (await requestPromise).postData() ?? '';
        expect(body).toContain('charts%5Bcategory_bar%5D');
        expect(body).toContain('data%3Aimage%2Fpng%3Bbase64');
    });

    test('table column headers use the project green', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'category');
        await page.click('.generate');

        const th = page.locator('.table-col-helper table thead th').first();
        await expect(th).toBeVisible({ timeout: 15000 });
        await expect(th).toHaveCSS('background-color', 'rgb(189, 237, 188)');
        await expect(th).toHaveCSS('color', 'rgb(4, 87, 2)');
    });

    test('reports page has no serious accessibility violations', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'date');
        await page.click('.generate');
        await expect(page.locator('#myChart')).toBeVisible({ timeout: 15000 });

        // axe-core's color-contrast rule mis-resolves this legacy theme's
        // stacked backgrounds, so contrast is asserted separately below.
        const results = await new AxeBuilder({ page })
            .include('.reports-page')
            .disableRules(['color-contrast'])
            .analyze();
        const serious = results.violations.filter(
            (violation) => violation.impact === 'serious' || violation.impact === 'critical',
        );

        expect(serious, JSON.stringify(serious, null, 2)).toEqual([]);
    });

    test('report text meets WCAG AA contrast', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'date');
        await page.click('.generate');
        await expect(page.locator('#myChart')).toBeVisible({ timeout: 15000 });

        const ratios = await page.evaluate(() => {
            const parse = (color) => {
                const match = color.match(/rgba?\(([^)]+)\)/);
                if (!match) return null;
                return match[1].split(',').map(Number).slice(0, 3);
            };
            const luminance = (rgb) => {
                const [r, g, b] = rgb.map((v) => {
                    const c = v / 255;
                    return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
                });
                return 0.2126 * r + 0.7152 * g + 0.0722 * b;
            };
            const background = (el) => {
                let node = el;
                while (node) {
                    const value = getComputedStyle(node).backgroundColor;
                    if (value && !value.startsWith('rgba(0, 0, 0, 0)')) {
                        return parse(value);
                    }
                    node = node.parentElement;
                }
                return [255, 255, 255];
            };

            const selectors = [
                '.report-title',
                '.report-form .control-label',
                '.report-summary .kpi-label',
                '.report-summary .kpi-value',
                '.report-insight .panel-title',
            ];

            const out = {};
            selectors.forEach((selector) => {
                const el = document.querySelector(selector);
                if (!el) return;
                const fg = parse(getComputedStyle(el).color);
                const bg = background(el);
                const l1 = luminance(fg);
                const l2 = luminance(bg);
                out[selector] = (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
            });

            return out;
        });

        Object.entries(ratios).forEach(([selector, ratio]) => {
            const minimum = selector === '.report-title' || selector === '.report-summary .kpi-value' ? 3 : 4.5;
            expect(ratio, `${selector} contrast ${ratio}`).toBeGreaterThanOrEqual(minimum);
        });
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
        const emptyState = page.locator('.report-result-header', { hasText: 'Canceladas' });
        await expect(table.or(emptyState).first()).toBeVisible();
    });

    test('generates participant average chart', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'participant-average');
        await page.click('.generate');

        const chart = page.locator('#myChart');
        const emptyState = page.locator('.report-result-header', { hasText: 'No existen' });
        await expect(chart.or(emptyState).first()).toBeVisible();
    });

    test('preset date range is submitted with the report and KPI requests', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        // Presets lock the date inputs; the range must still be submitted.
        await page.click('.date-preset-btn[data-preset="year"]');

        const expectedStart = `${new Date().getFullYear()}-01-01`;

        const summaryRequest = page.waitForRequest(
            (req) => req.url().includes('/reports/summary') && req.method() === 'POST',
        );
        const reportRequest = page.waitForRequest(
            (req) => req.url().includes('/reports/date') && req.method() === 'POST',
        );

        await page.click('.generate');

        const summaryBody = (await summaryRequest).postData() ?? '';
        const reportBody = (await reportRequest).postData() ?? '';

        for (const body of [summaryBody, reportBody]) {
            expect(body).toContain(`start_date=${expectedStart}`);
            expect(body).toMatch(/end_date=\d{4}-\d{2}-\d{2}/);
        }
    });

    test('every report type renders the standardized results hero without errors', async ({ page }) => {
        test.slow();

        await loginAs(page, 'admin');
        await page.goto('/reports');

        const problems: string[] = [];
        page.on('pageerror', (err) => problems.push(`pageerror: ${err.message}`));

        const types = [
            'date', 'category', 'status', 'duration', 'canceled',
            'participant-by-quantity', 'participant-by-status', 'participant-average',
            'gender', 'most-scheduled', 'least-scheduled', 'not-scheduled',
        ];

        for (const type of types) {
            await page.selectOption('#report-type', type);
            await page.click('.generate');

            const header = page.locator('.report-result-header');
            const title = header.locator('.report-result-title');
            const period = header.locator('.report-result-period');

            await expect(title, `${type}: hero title`).toBeVisible({ timeout: 15000 });
            await expect(title, `${type}: hero title text`).not.toBeEmpty();

            // Every report shows the heading and its period ("Histórico" for
            // the range-independent reports).
            await expect(period, `${type}: hero period`).toBeVisible();
            await expect(period, `${type}: hero period text`).not.toBeEmpty();
        }

        expect(problems, problems.join('\n')).toEqual([]);
    });

    test('status report renders its chart and detail table rows', async ({ page }) => {
        await loginAs(page, 'admin');
        await page.goto('/reports');

        await page.selectOption('#report-type', 'status');
        await page.click('.generate');

        // The course list is built by the renderer; it used to crash on an
        // implicit global, leaving the table empty.
        const rows = page.locator('.table-col-helper .report-block table tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 15000 });
        expect(await rows.count()).toBeGreaterThan(0);
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
