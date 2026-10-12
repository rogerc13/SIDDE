import { $ , moment, decorateSortableReportTables, initTablePagination, renderTablePage, getPaginationState, sortTableByColumn, participantStatusSelect, loadTheme } from './shared.js';
import { renderers } from './renderers/index.js';
import { applyCanvasA11y, enhanceReportTables } from './ui.js';
$(document).ready(function(){
    $(".loader").addClass("hidden");
    $(".participant-status-container").hide();

    // Sync chart colors with the backend palette (single source of truth).
    loadTheme();

    /**
     * Serialize the filter form while guaranteeing the selected date range is
     * always submitted. Presets lock the date inputs (disabled), and jQuery's
     * serialize() drops disabled fields, which previously made every request
     * fall back to the server's default range.
     */
    function reportFormFields() {
        var fields = $('.report-form').serializeArray().filter(function (item) {
            return item.name !== 'start_date' && item.name !== 'end_date';
        });

        var start = $('#start_date').val();
        var end = $('#end_date').val();
        if (start) {
            fields.push({ name: 'start_date', value: start });
        }
        if (end) {
            fields.push({ name: 'end_date', value: end });
        }

        return fields;
    }

    function serializeReportForm() {
        return $.param(reportFormFields());
    }

    // Sorting: use event delegation so it works for dynamically generated report tables.
    $(document)
        .off('click.reportSort', '.table-col-helper table thead th')
        .on('click.reportSort', '.table-col-helper table thead th', function(){
            const th = this;
            const tableEl = th.closest('table');
            if (!tableEl) {
                return;
            }

            // Do not interfere with DataTables (if any table gets upgraded elsewhere).
            if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.dataTable) {
                const $table = $(tableEl);
                if ($table.hasClass('dataTable')) {
                    return;
                }
            }

            const headerRow = th.parentElement;
            const ths = Array.from(headerRow.children);
            const colIndex = ths.indexOf(th);
            if (colIndex < 0) {
                return;
            }

            const currentCol = tableEl.dataset.reportSortCol;
            const currentDir = tableEl.dataset.reportSortDir;
            const nextDir = (String(currentCol) === String(colIndex) && currentDir === 'asc') ? 'desc' : 'asc';

            tableEl.dataset.reportSortCol = String(colIndex);
            tableEl.dataset.reportSortDir = nextDir;

            // Update aria-sort
            ths.forEach(cell => {
                if (cell && cell.tagName === 'TH') {
                    cell.setAttribute('aria-sort', 'none');

                    // Reset icons in this header row
                    const icon = cell.querySelector('.report-sort-icon');
                    if (icon) {
                        icon.classList.remove('fa-sort-asc', 'fa-sort-desc');
                        // Ensure base sort icon exists
                        if (!icon.classList.contains('fa-sort')) {
                            icon.classList.add('fa-sort');
                        }
                    }
                }
            });
            th.setAttribute('aria-sort', nextDir === 'asc' ? 'ascending' : 'descending');

            // Toggle icon on the active column
            const activeIcon = th.querySelector('.report-sort-icon');
            if (activeIcon) {
                activeIcon.classList.remove('fa-sort');
                activeIcon.classList.toggle('fa-sort-asc', nextDir === 'asc');
                activeIcon.classList.toggle('fa-sort-desc', nextDir === 'desc');
            }

            sortTableByColumn(tableEl, colIndex, nextDir);
        })
        .off('keydown.reportSort', '.table-col-helper table thead th')
        .on('keydown.reportSort', '.table-col-helper table thead th', function(e){
            // Enter/Space should activate sorting for accessibility.
            const key = e.key || e.keyCode;
            if (key === 'Enter' || key === ' ' || key === 13 || key === 32) {
                e.preventDefault();
                $(this).trigger('click');
            }
        });

    // Pagination controls (delegated so it works for dynamic tables)
    $(document)
        .off('click.reportPaginationPrev', '.report-pagination-prev')
        .on('click.reportPaginationPrev', '.report-pagination-prev', function(){
            const controls = this.closest('.report-pagination-controls');
            if (!controls) {
                return;
            }
            const tableId = controls.getAttribute('data-report-pagination-for');
            if (!tableId) {
                return;
            }
            const tableEl = document.getElementById(tableId);
            if (!tableEl) {
                return;
            }
            const current = Number(tableEl.dataset.reportPage || 1);
            tableEl.dataset.reportPage = String(Math.max(1, current - 1));
            renderTablePage(tableEl);
        })
        .off('click.reportPaginationNext', '.report-pagination-next')
        .on('click.reportPaginationNext', '.report-pagination-next', function(){
            const controls = this.closest('.report-pagination-controls');
            if (!controls) {
                return;
            }
            const tableId = controls.getAttribute('data-report-pagination-for');
            if (!tableId) {
                return;
            }
            const tableEl = document.getElementById(tableId);
            if (!tableEl) {
                return;
            }
            const state = getPaginationState(tableEl);
            const tbody = tableEl.tBodies && tableEl.tBodies[0];
            const totalRows = tbody ? (tbody.rows ? tbody.rows.length : 0) : 0;
            const totalPages = state.pageSize ? Math.max(1, Math.ceil(totalRows / state.pageSize)) : 1;
            tableEl.dataset.reportPage = String(Math.min(totalPages, state.page + 1));
            renderTablePage(tableEl);
        });

    function toYmd(date){
        // Prefer moment if present; fallback to native Date
        if (typeof moment === 'function') {
            return moment(date).format('YYYY-MM-DD');
        }
        const d = (date instanceof Date) ? date : new Date(date);
        return d.toISOString().slice(0, 10);
    }

    function getRangeDays(startYmd, endYmd){
        if (!startYmd || !endYmd) {
            return null;
        }
        if (typeof moment === 'function') {
            const start = moment(startYmd, 'YYYY-MM-DD');
            const end = moment(endYmd, 'YYYY-MM-DD');
            if (!start.isValid() || !end.isValid()) {
                return null;
            }
            return end.diff(start, 'days') + 1;
        }
        const start = new Date(startYmd);
        const end = new Date(endYmd);
        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
            return null;
        }
        const msPerDay = 24 * 60 * 60 * 1000;
        return Math.floor((end.getTime() - start.getTime()) / msPerDay) + 1;
    }

    function updateStepOptionsBySelectedRange(){
        const startYmd = $('#start_date').val();
        const endYmd = $('#end_date').val();
        const rangeDays = getRangeDays(startYmd, endYmd);

        // If the range is invalid (end before start), don't lock the user out; just enable all.
        $('#step option').prop('disabled', false);

        if (rangeDays === null || rangeDays <= 0) {
            return;
        }

        const stepMinDays = {
            '1 day': 1,
            '1 week': 7,
            '15 days': 15,
            '1 month': 30,
            '4 months': 120,
            '6 months': 180,
            '1 year': 365,
        };

        $('#step option').each(function(){
            const value = $(this).val();
            const minDays = stepMinDays[value];
            if (minDays !== undefined && minDays > rangeDays) {
                $(this).prop('disabled', true);
            }
        });

        // If current selection became invalid, reset to daily.
        if ($('#step option:selected').prop('disabled')) {
            $('#step').val('1 day').change();
        }
    }

    function updateGenerateButtonDisabledState(){
        const today = toYmd(new Date());
        const startYmd = $('#start_date').val();
        const endYmd = $('#end_date').val();
        const disable = (startYmd === today && endYmd === today);
        $('.generate').prop('disabled', disable);
    }

    // Set sane defaults (previously defaulted to "1 Mes")
    if (!$('#end_date').val()) {
        $('#end_date').val(toYmd(new Date()));
    }
    if (!$('#start_date').val()) {
        if (typeof moment === 'function') {
            $('#start_date').val(moment($('#end_date').val(), 'YYYY-MM-DD').subtract(1, 'month').format('YYYY-MM-DD'));
        } else {
            const end = new Date($('#end_date').val());
            end.setMonth(end.getMonth() - 1);
            $('#start_date').val(toYmd(end));
        }
    }

    updateStepOptionsBySelectedRange();
    updateGenerateButtonDisabledState();

    // ── Date-range presets ──────────────────────────────────────────────
    const DATE_PRESETS = {
        '7d':      () => ({ start: moment().subtract(6, 'days'),  end: moment(),                   step: '1 day' }),
        '30d':     () => ({ start: moment().subtract(29, 'days'), end: moment(),                   step: '1 day' }),
        'month':   () => ({ start: moment().startOf('month'),     end: moment(),                   step: '1 day' }),
        'quarter': () => ({ start: moment().startOf('quarter'),   end: moment(),                   step: '1 month' }),
        'year':    () => ({ start: moment().startOf('year'),      end: moment(),                   step: '1 month' }),
    };

    function applyPreset(preset) {
        const fn = DATE_PRESETS[preset];
        if (!fn) { return; } // 'custom' or unknown → do nothing

        const { start, end, step } = fn();
        $('#start_date').val(start.format('YYYY-MM-DD'));
        $('#end_date').val(end.format('YYYY-MM-DD'));
        $('#step').val(step);

        // Disable manual editing for non-custom presets
        $('#start_date, #end_date').prop('disabled', true);

        updateStepOptionsBySelectedRange();
        updateGenerateButtonDisabledState();
    }

    function highlightPreset(preset) {
        $('.date-preset-btn').removeClass('active');
        $(`.date-preset-btn[data-preset="${preset}"]`).addClass('active');
    }

    // Highlight the default (30d) on load
    highlightPreset('30d');

    // Click handler for preset buttons
    $(document).on('click', '.date-preset-btn', function () {
        const preset = $(this).data('preset');
        highlightPreset(preset);

        if (preset === 'custom') {
            // Re-enable manual date inputs
            $('#start_date, #end_date').prop('disabled', false);
            return;
        }

        applyPreset(preset);
    });

    // Participant status selector removed from the view; keep container hidden if present.

    $('#start_date, #end_date').off().on('change', function(){
        const start = $('#start_date').val();
        const end = $('#end_date').val();

        // If user selected an inverted range, swap to keep UX friendly
        if (start && end) {
            const rangeDays = getRangeDays(start, end);
            if (rangeDays !== null && rangeDays <= 0) {
                $('#start_date').val(end);
                $('#end_date').val(start);
            }
        }

        // Switch to "Personalizado" when the user manually changes dates
        highlightPreset('custom');
        $('#start_date, #end_date').prop('disabled', false);

        updateStepOptionsBySelectedRange();
        updateGenerateButtonDisabledState();
    });

    $('.print-report').off().on('click', function (e){
        e.preventDefault();

        // Build a POST form so the browser handles the file download.
        const $form = $('.report-form');
        if (!$form.length) {
            return;
        }

        const action = '/reports/pdf';
        const data = reportFormFields();

        function getPanelTitleFor(el){
            if (!el) {
                return '';
            }
            const panel = el.closest('.panel');
            if (panel) {
                const t = panel.querySelector('.panel-title');
                if (t && t.textContent) {
                    return t.textContent.replace(/\s+/g, ' ').trim();
                }
            }
            return '';
        }

        function canvasToPngDataUrl(canvas){
            try {
                const w = canvas.width || 0;
                const h = canvas.height || 0;
                if (!w || !h) {
                    return null;
                }

                const tmp = document.createElement('canvas');
                tmp.width = w;
                tmp.height = h;
                const ctx = tmp.getContext('2d');
                if (!ctx) {
                    return null;
                }
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, w, h);
                ctx.drawImage(canvas, 0, 0);

                // PNG is lossless and keeps chart text/lines crisp in PDFs.
                return tmp.toDataURL('image/png');
            } catch (_e) {
                return null;
            }
        }

        // Collect chart images in DOM order.
        // Prefer .row-graphs, but fall back to any visible canvases inside the report container.
        let canvases = Array.from(document.querySelectorAll('.row-graphs canvas'));
        if (!canvases.length) {
            canvases = Array.from(document.querySelectorAll('.report-view canvas'));
        }
        canvases.forEach((canvas, idx) => {
            const img = canvasToPngDataUrl(canvas);
            if (!img) {
                return;
            }
            const title = getPanelTitleFor(canvas) || `Gráfico ${idx + 1}`;
            data.push({ name: `charts[${idx}][title]`, value: title });
            data.push({ name: `charts[${idx}][image]`, value: img });
        });

        // Ensure report-type is included (it is, but keep this defensive)
        const selected = $('.selector').find(':selected').val();
        const hasType = data.some(i => i && i.name === 'report-type');
        if (!hasType) {
            data.push({ name: 'report-type', value: selected });
        }

        const formEl = document.createElement('form');
        formEl.method = 'POST';
        formEl.action = action;
        formEl.style.display = 'none';

        data.forEach((item) => {
            if (!item || !item.name) {
                return;
            }
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = item.name;
            input.value = item.value ?? '';
            formEl.appendChild(input);
        });

        document.body.appendChild(formEl);
        formEl.submit();
        formEl.remove();
    });

    // re-run the report when a status filter is chosen
    $(document).on('change', '#participant_status', function () {
        if ($('.selector').find(':selected').val() === 'participant-by-status') {
            $('.generate').trigger('click');
        }
    });

    $.ajaxSetup({
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
        });

    // participant status filter: only shown for the participant-by-status report
    let participantStatusOptionsLoaded = false;

    //Reports by type selected
    $('.generate').click(function (e) {
            //data to send
            let selected = $('.selector').find(':selected').val();

            const showStatusFilter = selected === 'participant-by-status' || selected === 'participant-average';
            $('.participant-status-container').toggle(showStatusFilter);
            if (showStatusFilter && !participantStatusOptionsLoaded) {
                participantStatusSelect();
                participantStatusOptionsLoaded = true;
            }

            // The two "Histórico" reports ignore the date range and interval by
            // design; disable those filters so they can't be submitted as a
            // silent no-op, and so the UI reflects the actual query scope.
            const historicalTypes = ['most-scheduled', 'not-scheduled'];
            const disableDateFilters = historicalTypes.includes(selected);
            $('.report-date-row').toggle(!disableDateFilters);
            $('#date-step-col').toggle(!disableDateFilters);
            $('#step').prop('disabled', disableDateFilters);
            $('.date-preset-btn').prop('disabled', disableDateFilters);

            // Drop any previously chosen participant status when leaving the
            // participant-by-status report so it isn't carried into other types.
            if (!showStatusFilter) {
                $('#participant_status').val('');
            }

            let formData = serializeReportForm();
            // Keep the executive KPI strip aligned with the chosen range.
            loadSummary();
            //reset graph container
            $('.graph-container').children().remove();
            $('.doughnut-container').children().remove();
            $('.graph-container').append('<canvas id="myChart"></canvas>');
            $('.doughnut-container').append('<canvas id="doughnut"></canvas>');
            
            $.ajax({
            type: "POST",
            data: formData,
            url: '/reports/'+selected,
            dataType: 'json',
            beforeSend: function(){
                $(".loader").removeClass("hidden");
                $('.reports-page').addClass('is-loading').attr('aria-busy', 'true');
            },
            success: function(response){
                $(".loader").addClass("hidden");
                $('.print-report').removeAttr('disabled');

                // The reporting API returns a normalized envelope: report
                // specific data lives under "raw", while summary/insight are
                // presented above the results.
                var payload = response || {};
                var data = payload.raw || payload;

                renderReportInsight(payload);
                renderSummaryStrip(payload.summary);

                const renderer = renderers[selected];
                if (typeof renderer === 'function') {
                    // Pass both the report-specific data and the full envelope
                    // (renderers read the canonical title/dates from payload.meta).
                    renderer(data, payload);
                }

                // After the report draws its tables, mark headers as sortable.
                decorateSortableReportTables($('.table-col-helper'));
                enhanceReportTables(document.querySelector('.table-col-helper'));
                applyCanvasA11y(document.querySelector('.row-graphs'));
                $('.reports-page').removeClass('is-loading').removeAttr('aria-busy');
            }, 
            error:function(response){
                console.log("error "+response);
                $(".loader").addClass("hidden");
                $('.reports-page').removeClass('is-loading').removeAttr('aria-busy');
            }
            });
            e.preventDefault();
        });

    /**
     * Render the deterministic executive summary paragraphs.
     */
    function renderReportInsight(payload) {
        var $box = $('.report-insight');
        if (!$box.length) {
            return;
        }

        var lines = (payload && payload.insight) || [];
        if (!lines.length) {
            $box.addClass('hidden').empty();
            return;
        }

        var html = '<div class="panel panel-info report-insight-panel">'
            + '<div class="panel-heading"><h4 class="panel-title">Resumen Ejecutivo</h4></div>'
            + '<div class="panel-body"><ul class="report-insight-list">';

        lines.forEach(function (line) {
            html += '<li>' + $('<div>').text(line).html() + '</li>';
        });

        html += '</ul></div></div>';
        $box.html(html).removeClass('hidden');
    }

    /**
     * Headline KPI cards. Accepts either the report envelope summary (empty)
     * or the /reports/summary payload.
     */
    function renderSummaryStrip(summary) {
        var $box = $('.report-summary');
        if (!$box.length) {
            return;
        }

        var kpis = (summary && summary.kpis) ? summary.kpis : [];
        if (!kpis.length) {
            return;
        }

        var html = '';
        kpis.forEach(function (kpi) {
            var delta = '';
            if (kpi.delta !== null && kpi.delta !== undefined) {
                var positive = kpi.delta >= 0;
                delta = '<span class="kpi-delta ' + (positive ? 'kpi-up' : 'kpi-down') + '">'
                    + (positive ? '▲' : '▼') + ' ' + Math.abs(kpi.delta) + '%</span>';
            }

            html += '<div class="kpi-card" title="' + $('<div>').text(kpi.formula || '').html() + '">'
                + '<div class="kpi-label">' + $('<div>').text(kpi.label || '').html() + '</div>'
                + '<div class="kpi-value">' + $('<div>').text(kpi.display || '').html() + '</div>'
                + '<div class="kpi-unit">' + $('<div>').text(kpi.unit || '').html() + '</div>'
                + delta
                + '</div>';
        });

        $box.html(html).removeClass('hidden');
    }

    /**
     * Fetch the executive KPI strip for the currently selected range.
     */
    function loadSummary() {
        if (!$('.report-summary').length) {
            return;
        }

        $.ajax({
            type: "POST",
            data: serializeReportForm(),
            url: '/reports/summary',
            dataType: 'json',
            success: function (summary) {
                renderSummaryStrip(summary);
            }
        });
    }

    // Export the current report as CSV/XLSX using the same filters.
    $(document).on('click', '.export-report', function (e) {
        e.preventDefault();

        var format = $(this).data('format') || 'csv';
        var fields = reportFormFields();
        fields.push({ name: 'format', value: format });

        var $form = $('<form>', { method: 'POST', action: '/reports/export' }).hide();
        fields.forEach(function (item) {
            $('<input>', { type: 'hidden', name: item.name, value: item.value ?? '' }).appendTo($form);
        });
        $form.appendTo('body').submit().remove();
    });

    // Populate the KPI strip on first load.
    loadSummary();
})