import { $ } from "./globals.js";
let __reportTableIdCounter = 0;

export function ensureTableId(tableEl){
    if (!tableEl) {
        return null;
    }
    if (tableEl.id) {
        return tableEl.id;
    }
    __reportTableIdCounter += 1;
    tableEl.id = `report-table-${__reportTableIdCounter}`;
    return tableEl.id;
}

export function getPaginationState(tableEl){
    const pageSize = Number(tableEl.dataset.reportPageSize || 0);
    const page = Number(tableEl.dataset.reportPage || 1);
    return {
        pageSize: Number.isFinite(pageSize) && pageSize > 0 ? pageSize : null,
        page: Number.isFinite(page) && page > 0 ? page : 1,
    };
}

export function renderTablePage(tableEl){
    if (!tableEl || !tableEl.tBodies || !tableEl.tBodies[0]) {
        return;
    }
    const state = getPaginationState(tableEl);
    if (!state.pageSize) {
        return;
    }

    const tbody = tableEl.tBodies[0];
    const rows = Array.from(tbody.rows || []);
    const totalRows = rows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / state.pageSize));
    const page = Math.min(Math.max(1, state.page), totalPages);
    tableEl.dataset.reportPage = String(page);

    const startIdx = (page - 1) * state.pageSize;
    const endIdx = startIdx + state.pageSize;

    rows.forEach((row, idx) => {
        row.style.display = (idx >= startIdx && idx < endIdx) ? '' : 'none';
    });

    const pager = document.querySelector(`.report-pagination-controls[data-report-pagination-for="${CSS.escape(tableEl.id)}"]`);
    if (pager) {
        const label = pager.querySelector('.report-pagination-label');
        const prevBtn = pager.querySelector('.report-pagination-prev');
        const nextBtn = pager.querySelector('.report-pagination-next');

        if (label) {
            label.textContent = `Página ${page} de ${totalPages} (${totalRows})`;
        }
        if (prevBtn) {
            prevBtn.disabled = page <= 1;
        }
        if (nextBtn) {
            nextBtn.disabled = page >= totalPages;
        }
    }
}

export function initTablePagination(tableEl, pageSize){
    if (!tableEl) {
        return;
    }
    ensureTableId(tableEl);

    const size = Number(pageSize);
    if (!Number.isFinite(size) || size <= 0) {
        return;
    }

    tableEl.dataset.reportPageSize = String(size);
    if (!tableEl.dataset.reportPage) {
        tableEl.dataset.reportPage = '1';
    }

    const existing = document.querySelector(`.report-pagination-controls[data-report-pagination-for="${CSS.escape(tableEl.id)}"]`);
    if (!existing) {
        const controls = document.createElement('div');
        controls.className = 'report-pagination-controls text-center';
        controls.setAttribute('data-report-pagination-for', tableEl.id);
        controls.style.marginTop = '8px';

        controls.innerHTML = `
            <button type="button" class="btn btn-default btn-sm report-pagination-prev">Anterior</button>
            <span class="report-pagination-label" style="margin: 0 10px;"></span>
            <button type="button" class="btn btn-default btn-sm report-pagination-next">Siguiente</button>
        `;

        // Insert right after the table
        tableEl.parentNode.insertBefore(controls, tableEl.nextSibling);
    }

    renderTablePage(tableEl);
}
export function normalizeSortText(value){
    if (value === null || value === undefined) {
        return '';
    }
    return String(value).replace(/\s+/g, ' ').trim();
}

export function parseLocaleNumber(text){
    const t0 = normalizeSortText(text);
    if (!t0) {
        return null;
    }

    // Remove non-numeric characters except separators and minus.
    let t = t0.replace(/\s/g, '').replace(/[^0-9,\.\-]/g, '');
    if (!t || t === '-' || t === '.' || t === ',') {
        return null;
    }

    const lastDot = t.lastIndexOf('.');
    const lastComma = t.lastIndexOf(',');

    if (lastDot !== -1 && lastComma !== -1) {
        // If both separators exist, decide which is decimal by last occurrence.
        if (lastComma > lastDot) {
            // 1.234,56 -> 1234.56
            t = t.replace(/\./g, '').replace(',', '.');
        } else {
            // 1,234.56 -> 1234.56
            t = t.replace(/,/g, '');
        }
    } else if (lastComma !== -1) {
        // Only comma. If it looks like decimal, convert to dot; else treat as thousand sep.
        if (/,-?\d{1,4}$/.test(t)) {
            t = t.replace(',', '.');
        } else if (/,\d{1,4}$/.test(t)) {
            t = t.replace(',', '.');
        } else {
            t = t.replace(/,/g, '');
        }
    } else if (lastDot !== -1) {
        // Only dot. If it doesn't look like decimal, treat as thousand sep.
        if (!/\.\d{1,4}$/.test(t)) {
            t = t.replace(/\./g, '');
        }
    }

    const num = Number(t);
    return Number.isFinite(num) ? num : null;
}

export function parseDateValue(text){
    const t0 = normalizeSortText(text);
    if (!t0) {
        return null;
    }
    // Take first token in case it includes time.
    const t = t0.split(' ')[0];

    // YYYY-MM-DD
    let m = t.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (m) {
        const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
        const time = d.getTime();
        return Number.isNaN(time) ? null : time;
    }

    // DD/MM/YYYY
    m = t.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (m) {
        const d = new Date(Number(m[3]), Number(m[2]) - 1, Number(m[1]));
        const time = d.getTime();
        return Number.isNaN(time) ? null : time;
    }

    // DD-MM-YYYY
    m = t.match(/^(\d{2})-(\d{2})-(\d{4})$/);
    if (m) {
        const d = new Date(Number(m[3]), Number(m[2]) - 1, Number(m[1]));
        const time = d.getTime();
        return Number.isNaN(time) ? null : time;
    }

    return null;
}

export function getCellSortValue(cell){
    if (!cell) {
        return '';
    }
    // Prefer data-sort-value if present, so views can override without changing display.
    const attr = cell.getAttribute('data-sort-value');
    if (attr !== null && attr !== undefined) {
        return attr;
    }
    return cell.textContent;
}

export function sortTableByColumn(tableEl, colIndex, direction){
    if (!tableEl) {
        return;
    }
    const tbody = tableEl.tBodies && tableEl.tBodies[0];
    if (!tbody) {
        return;
    }
    const rows = Array.from(tbody.rows || []);
    if (rows.length <= 1) {
        return;
    }

    const collator = (typeof Intl !== 'undefined' && Intl.Collator)
        ? new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' })
        : null;

    const dir = direction === 'desc' ? -1 : 1;

    const decorated = rows.map((row, idx) => {
        const cell = row.cells ? row.cells[colIndex] : null;
        const raw = normalizeSortText(getCellSortValue(cell));
        const num = parseLocaleNumber(raw);
        const date = parseDateValue(raw);

        return {
            row,
            idx,
            raw,
            num,
            date,
        };
    });

    decorated.sort((a, b) => {
        // Empty values always sort last (both directions)
        const aEmpty = !a.raw;
        const bEmpty = !b.raw;
        if (aEmpty && bEmpty) {
            return a.idx - b.idx;
        }
        if (aEmpty) {
            return 1;
        }
        if (bEmpty) {
            return -1;
        }

        // If both parse as numbers, compare numbers
        if (a.num !== null && b.num !== null) {
            if (a.num === b.num) {
                return a.idx - b.idx;
            }
            return (a.num < b.num ? -1 : 1) * dir;
        }

        // If both parse as dates, compare dates
        if (a.date !== null && b.date !== null) {
            if (a.date === b.date) {
                return a.idx - b.idx;
            }
            return (a.date < b.date ? -1 : 1) * dir;
        }

        // Otherwise, compare as text (natural sort when available)
        const cmp = collator ? collator.compare(a.raw, b.raw) : a.raw.localeCompare(b.raw);
        if (cmp === 0) {
            return a.idx - b.idx;
        }
        return cmp * dir;
    });

    // Re-append in sorted order
    const frag = document.createDocumentFragment();
    decorated.forEach(item => frag.appendChild(item.row));
    tbody.appendChild(frag);

    // If the table is paginated, re-render the current page
    if (tableEl && tableEl.dataset && tableEl.dataset.reportPageSize) {
        renderTablePage(tableEl);
    }
}

export function decorateSortableReportTables($root){
    const root = ($root && $root.length) ? $root.get(0) : document;
    const tables = root.querySelectorAll('table');

    tables.forEach(tableEl => {
        if (tableEl.dataset && tableEl.dataset.reportSortReady === '1') {
            return;
        }
        const thead = tableEl.tHead;
        if (!thead || !thead.rows || !thead.rows.length) {
            return;
        }

        const headerRow = thead.rows[0];
        const ths = Array.from(headerRow.cells || []).filter(cell => cell && cell.tagName === 'TH');
        if (ths.length === 0) {
            return;
        }

        // Mark headers as interactive
        ths.forEach(th => {
            th.style.cursor = 'pointer';
            th.setAttribute('tabindex', '0');
            th.setAttribute('role', 'button');
            if (!th.getAttribute('aria-sort')) {
                th.setAttribute('aria-sort', 'none');
            }
            if (!th.getAttribute('title')) {
                th.setAttribute('title', 'Click para ordenar');
            }

            // Add a sort icon indicator using existing Font Awesome (FA4 style).
            // Only add once per header.
            if (!th.querySelector('.report-sort-icon')) {
                const icon = document.createElement('i');
                icon.className = 'fa fa-sort report-sort-icon text-muted';
                icon.setAttribute('aria-hidden', 'true');
                icon.style.marginLeft = '6px';
                th.appendChild(icon);
            }
        });

        tableEl.dataset.reportSortReady = '1';
    });
}

