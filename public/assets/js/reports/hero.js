import { $ } from './globals.js';
import { formatServerDate } from './util.js';
import { escapeHtml } from './ui.js';

/**
 * Canonical period label for the report hero: the selected range when the
 * report is date-ranged, otherwise "Histórico".
 */
function periodLabel(meta) {
    const start = meta && meta.startDate ? formatServerDate(meta.startDate) : '';
    const end = meta && meta.endDate ? formatServerDate(meta.endDate) : '';

    if (start && end) {
        return `Período: ${start} – ${end}`;
    }

    return 'Histórico';
}

/**
 * Single source of truth for the results heading rendered between the filter
 * toolbar and the KPI strip. Every report type uses this so the title and
 * period share one wording, structure and typography.
 *
 * The heading is intentionally value-free: the KPI strip owns the numbers.
 * An optional `empty` message is shown alongside the heading when a report
 * has no rows.
 *
 * @param {object} options
 * @param {object} options.meta  Report envelope meta ({ title, startDate, endDate }).
 * @param {string} [options.empty] Empty-state message shown next to the heading.
 */
export function renderReportHero({ meta = {}, empty = null } = {}) {
    const $target = $('.course-amount-number');
    if (!$target.length) {
        return;
    }

    const title = escapeHtml(meta.title || 'Reporte');
    const period = escapeHtml(periodLabel(meta));
    const note = empty !== null
        ? `<div class="report-result-empty">${escapeHtml(empty)}</div>`
        : '';

    $target.html(
        `<div class="report-result-header">
            <div class="report-result-titles">
                <h2 class="report-result-title">${title}</h2>
                <div class="report-result-period">${period}</div>
            </div>
            ${note}
        </div>`
    );
}
