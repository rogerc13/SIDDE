// Shared DOM helpers for the report renderers.

export function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

export function emptyState(message) {
    return `<div class="report-empty">${escapeHtml(message)}</div>`;
}

/**
 * Give every report table an accessible caption and column scopes.
 */
export function enhanceReportTables(root) {
    const scope = root || document;

    scope.querySelectorAll('table').forEach((table) => {
        if (!table.querySelector('caption')) {
            const panel = table.closest('.panel');
            const title = panel && panel.querySelector('.panel-title')
                ? panel.querySelector('.panel-title').textContent.replace(/\s+/g, ' ').trim()
                : '';

            if (title) {
                const caption = document.createElement('caption');
                caption.className = 'sr-only';
                caption.textContent = title;
                table.insertBefore(caption, table.firstChild);
            }
        }

        table.querySelectorAll('thead th').forEach((th) => {
            if (!th.getAttribute('scope')) {
                th.setAttribute('scope', 'col');
            }
        });
    });
}

/**
 * Give chart canvases a role and a descriptive label derived from their card.
 */
export function applyCanvasA11y(root) {
    const scope = root || document;

    scope.querySelectorAll('canvas').forEach((canvas) => {
        if (!canvas.getAttribute('role')) {
            canvas.setAttribute('role', 'img');
        }

        if (!canvas.getAttribute('aria-label')) {
            const panel = canvas.closest('.panel');
            const title = panel && panel.querySelector('.panel-title')
                ? panel.querySelector('.panel-title').textContent.replace(/\s+/g, ' ').trim()
                : '';

            canvas.setAttribute('aria-label', title || 'Gráfico del reporte');
        }
    });
}
