@push('CSS')
<link rel="stylesheet" href="{{url('assets/js/fullcalendar-2/fullcalendar.min.css')}}">
<style>
    .wizard-steps { list-style: none; padding: 0; margin: 0 0 10px; display: flex; justify-content: center; gap: 0; }
    .wizard-steps li { flex: 1; text-align: center; position: relative; padding: 6px 0; }
    .wizard-steps li .step-number { display: inline-block; width: 24px; height: 24px; line-height: 24px; border-radius: 50%; background: #ddd; color: #999; font-weight: bold; margin-bottom: 3px; }
    .wizard-steps li.active .step-number { background: #3498db; color: #fff; }
    .wizard-steps li.completed .step-number { background: #27ae60; color: #fff; }
    .wizard-steps li .step-label { display: block; font-size: 11px; color: #999; }
    .wizard-steps li.active .step-label { color: #333; font-weight: bold; }
    .wizard-steps li.completed .step-label { color: #27ae60; }
    .wizard-steps li::after { content: ''; position: absolute; top: 19px; left: 50%; width: 100%; height: 2px; background: #ddd; z-index: -1; }
    .wizard-steps li:last-child::after { display: none; }
    .wizard-steps li.completed::after { background: #27ae60; }
    .wizard-steps li.active::after { background: #3498db; }
    .wizard-step-panel { display: none; }
    .wizard-step-panel.active { display: block; }
    #wizard-calendar { height: 100%; }
    .fc-event { cursor: pointer; }
    .blocked-event { background: #999 !important; border-color: #999 !important; cursor: not-allowed !important; }
    .blocked-event-fac { background: #e67e22 !important; border-color: #e67e22 !important; cursor: not-allowed !important; background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, rgba(255,255,255,0.15) 5px, rgba(255,255,255,0.15) 10px) !important; }
    .review-table td { vertical-align: middle !important; }
    #duration-warning { display: none; }
    .modal-xl { width: 95%; max-width: 1400px; }
    .step2-layout { display: flex; gap: 20px; height: calc(90vh - 250px); min-height: 400px; }
    .step2-calendar { flex: 3; min-width: 0; display: flex; flex-direction: column; }
    .step2-calendar .calendar-env { flex: 1; min-height: 0; }
    .step2-sidebar { flex: 2; min-width: 280px; display: flex; flex-direction: column; overflow: hidden; }
    .step2-sidebar-header { flex-shrink: 0; margin-bottom: 12px; }
    .step2-sessions-scroll { flex: 1; overflow-y: auto; min-height: 0; }
    .sessions-table { font-size: 13px; }
    .sessions-table th, .sessions-table td { vertical-align: middle !important; padding: 8px 10px; }
    .sessions-table .actions { white-space: nowrap; width: 60px; }
    .sessions-table .actions .btn { padding: 2px 6px; }
    .duration-progress { margin-top: 8px; }
    #wizard-modal .modal-content { display: flex; flex-direction: column; height: calc(90vh - 40px); }
    #wizard-modal form#wizard-form { display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; }
    #wizard-modal .modal-body { flex: 1; overflow: hidden; min-height: 0; }
    #panel-step3 { overflow-y: auto; }
    .wizard-steps li { cursor: default; }
    .wizard-steps li.clickable { cursor: pointer; }
    .wizard-steps li.clickable:hover .step-label { text-decoration: underline; }
    .sessions-table tr:hover td { background: #fafafa; }
    .duration-chips { margin-top: 6px; }
    .duration-chips .btn { margin: 2px 4px 2px 0; padding: 2px 10px; }
    .help-block.field-error { color: #a94442; }
    .form-group.has-error .select2-choice { border-color: #a94442 !important; }
    .alert-wizard { margin-bottom: 10px; }
    #add-session-alert .alert { margin-bottom: 10px; }

    /* Add Session Modal */
    #add-session-modal .modal-content {
        border: 1px solid #ddd;
        -webkit-box-shadow: 0 5px 30px rgba(0, 0, 0, 0.2);
        -moz-box-shadow: 0 5px 30px rgba(0, 0, 0, 0.2);
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.2);
    }
    #add-session-modal .modal-header {
        border-top: 3px solid #00a651;
        padding: 14px 18px;
    }
    #add-session-modal .modal-title {
        font-weight: 600;
        font-size: 16px;
    }
    #add-session-modal .modal-body {
        padding: 20px 22px;
    }
    #add-session-modal .form-control {
        border: 1px solid #ccc;
        -webkit-transition: border-color 0.2s ease;
        -moz-transition: border-color 0.2s ease;
        transition: border-color 0.2s ease;
    }
    #add-session-modal .form-control:focus {
        border-color: #00a651;
        -webkit-box-shadow: 0 0 0 2px rgba(0, 166, 81, 0.15);
        -moz-box-shadow: 0 0 0 2px rgba(0, 166, 81, 0.15);
        box-shadow: 0 0 0 2px rgba(0, 166, 81, 0.15);
    }
    #add-session-modal .duration-chips {
        background: #f8f9fa;
        border: 1px solid #eee;
        border-radius: 4px;
        padding: 8px 10px;
        margin-top: 0;
    }
    #add-session-modal .duration-chips .btn {
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 3px;
        font-weight: 600;
        padding: 3px 12px;
        -webkit-transition: all 0.15s ease;
        -moz-transition: all 0.15s ease;
        transition: all 0.15s ease;
    }
    #add-session-modal .duration-chips .btn:hover {
        background: #00a651;
        border-color: #00a651;
        color: #fff;
    }
    #add-session-modal #modal-remaining {
        background: #f0faf4;
        border: 1px solid #d0e8da;
        border-radius: 4px;
        padding: 8px 12px;
        margin-bottom: 0;
        font-size: 13px;
    }
    #add-session-modal .modal-footer {
        border-top: 1px solid #e5e5e5;
        padding: 14px 22px;
    }
</style>
@endpush

@push('JS')
<script src="{{url('assets/js/bootstrap-timepicker.min.js')}}"></script>
<script>
var wizardSessions = [];
var courseDuration = 0;
var sessionCounter = 0;
var blockedSlotsCache = [];
var overlapPendingConfirm = false;
var locationPalette = ['#3498db', '#e74c3c', '#27ae60', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#16a085'];
var locationColorMap = {};

function showInlineAlert(containerId, type, message) {
    $('#' + containerId).html('<div class="alert alert-' + type + ' alert-dismissable fade in alert-wizard" role="alert" aria-live="polite">' +
        '<a href="#" class="close" data-dismiss="alert" aria-label="Cerrar">&times;</a>' +
        '<i class="entypo-warning"></i> ' + message + '</div>');
}

function clearInlineAlert(containerId) {
    $('#' + containerId).empty();
}

function setFieldError(selector, message) {
    var $el = $(selector);
    $el.closest('.form-group').addClass('has-error');
    var $help = $el.closest('.form-group').find('.help-block.field-error');
    if (message) {
        if ($help.length === 0) {
            $help = $('<span class="help-block field-error"></span>');
            $el.closest('.form-group').append($help);
        }
        $help.text(message);
    }
}

function clearFieldErrors(scope) {
    $(scope).find('.form-group').removeClass('has-error');
    $(scope).find('.help-block.field-error').remove();
}

function getLocationColor(locationId) {
    if (!locationColorMap[locationId]) {
        locationColorMap[locationId] = locationPalette[Object.keys(locationColorMap).length % locationPalette.length];
    }
    return locationColorMap[locationId];
}

function findBlockedOverlap(locationId, sessionDate, startTime, endTime) {
    var sMin = timeToMinutes(startTime);
    var eMin = timeToMinutes(endTime);
    for (var i = 0; i < blockedSlotsCache.length; i++) {
        var b = blockedSlotsCache[i];
        if (b.type !== 'location') continue;
        if (parseInt(b.location_id, 10) !== parseInt(locationId, 10)) continue;
        if (b.session_date !== sessionDate) continue;
        var bsMin = timeToMinutes(b.start_time);
        var beMin = timeToMinutes(b.end_time);
        if (sMin < beMin && eMin > bsMin) return b;
    }
    return null;
}

function findFacilitatorOverlap(sessionDate, startTime, endTime) {
    var sMin = timeToMinutes(startTime);
    var eMin = timeToMinutes(endTime);
    for (var i = 0; i < blockedSlotsCache.length; i++) {
        var b = blockedSlotsCache[i];
        if (b.type !== 'facilitator') continue;
        if (b.session_date !== sessionDate) continue;
        var bsMin = timeToMinutes(b.start_time);
        var beMin = timeToMinutes(b.end_time);
        if (sMin < beMin && eMin > bsMin) return b;
    }
    return null;
}

function programarAccion(url){
    document.getElementById("wizard-form").reset();
    $(".loader").addClass("hidden");
    $("#wizard-form").removeClass("hidden");
    $("[name=_method]").val("POST");
    $("#wizard-label").html("Programar Acción de Formación");
    $("#wizard-form").attr("action", url);
    wizardSessions = [];
    sessionCounter = 0;
    resetWizard();
    $("#wizard-modal").modal();

    $('.dat2').datepicker({ format: "yyyy-mm-dd", todayHighlight: true });

    $('#wizard-titulo').select2({ allowClear: true, placeholder: 'Seleccionar acción de formación' });
    $('#wizard-facilitador').select2({ allowClear: true, placeholder: 'Seleccionar facilitador' });

    $('#wizard-titulo').on('change', function() {
        var selected = $(this).find('option:selected');
        courseDuration = parseInt(selected.data('duration')) || 0;
        $('#duration-info').text('Duración del curso: ' + courseDuration + ' horas');
        updateDurationBar();
    });

    $('#wizard-facilitador').on('change', function() {
        refreshCalendarEvents();
    });

    $('#calendar-location-filter').select2({ allowClear: true, placeholder: 'Todas las ubicaciones' });
    $('#calendar-location-filter').on('change', function() {
        refreshCalendarEvents();
        renderLocationLegend();
    });
}

function resetWizard() {
    $('#step1').addClass('active');
    $('#step2, #step3').removeClass('active');
    $('#panel-step1').addClass('active');
    $('#panel-step2, #panel-step3').removeClass('active');
    $('.wizard-steps li').removeClass('active completed');
    $('.wizard-steps li:eq(0)').addClass('active');
    wizardSessions = [];
    locationColorMap = {};
    courseDuration = 0;
    $('#duration-info').text('');
    clearInlineAlert('wizard-alert');
    clearInlineAlert('wizard-submit-alert');
    $('#wizard-btn-prev').hide();
    $('#wizard-btn-next').show();
    $('#wizard-btn-submit').prop('disabled', true).hide();
    $('#calendar-location-filter').val('').trigger('change');
    $('#location-color-legend').empty().hide();
    renderSessionTable();
    if (window.wizardCalendar) {
        window.wizardCalendar.fullCalendar('destroy');
        window.wizardCalendar = null;
    }
}

function wizardGoToStep(step) {
    $('.wizard-step-panel').removeClass('active');
    $('#panel-step' + step).addClass('active');
    $('.wizard-steps li').removeClass('active completed');
    for (var i = 1; i < step; i++) {
        $('.wizard-steps li:eq(' + (i - 1) + ')').addClass('completed');
    }
    $('.wizard-steps li:eq(' + (step - 1) + ')').addClass('active');
    clearInlineAlert('wizard-alert');
    clearInlineAlert('wizard-submit-alert');

    if (step === 1) {
        $('#wizard-btn-prev').hide();
        $('#wizard-btn-next').show();
        $('#wizard-btn-submit').hide();
    } else if (step === 2) {
        $('#wizard-btn-prev').show();
        $('#wizard-btn-next').show();
        $('#wizard-btn-submit').hide();
    } else if (step === 3) {
        $('#wizard-btn-prev').show();
        $('#wizard-btn-next').hide();
        $('#wizard-btn-submit').show();
    }

    if (step === 2) {
        setTimeout(function() { initWizardCalendar(); }, 100);
    } else if (step === 3) {
        renderReviewPanel();
    }
}

function wizardNext(step) {
    clearInlineAlert('wizard-alert');
    if (step === 1) {
        if (!$('#wizard-titulo').val()) {
            showInlineAlert('wizard-alert', 'warning', 'Debe seleccionar una acción de formación.');
            return;
        }
        if (!$('#wizard-facilitador').val()) {
            showInlineAlert('wizard-alert', 'warning', 'Debe seleccionar un facilitador.');
            return;
        }
        courseDuration = parseInt($('#wizard-titulo option:selected').data('duration')) || 0;
        $('#duration-info').text('Duración del curso: ' + courseDuration + ' horas');
        updateDurationBar();
    }
    if (step === 2 && wizardSessions.length === 0) {
        showInlineAlert('wizard-alert', 'warning', 'Debe agregar al menos una sesión antes de continuar.');
        return;
    }
    wizardGoToStep(step + 1);
}

function wizardStepClick(step) {
    var current = getCurrentStep();
    if (step >= current) return;
    clearInlineAlert('wizard-alert');
    if (step === 1) {
        wizardGoToStep(1);
    } else if (step === 2) {
        wizardGoToStep(2);
    }
}

function wizardPrev(step) {
    wizardGoToStep(step - 1);
}

function initWizardCalendar() {
    if (window.wizardCalendar) {
        $('#wizard-calendar').fullCalendar('destroy');
    }

    var calContainer = $('.step2-calendar');
    var helperText = calContainer.children('p').first();
    var calHeight = calContainer.height() - (helperText.length ? helperText.outerHeight(true) + 8 : 0);

    window.wizardCalendar = $('#wizard-calendar').fullCalendar({
        header: { left: 'prev,next today', center: 'title', right: 'month,agendaWeek,agendaDay' },
        defaultView: 'agendaWeek',
        slotMinutes: 15,
        allDaySlot: false,
        minTime: '06:00:00',
        maxTime: '22:00:00',
        contentHeight: calHeight,
        selectable: true,
        selectHelper: true,
        selectMinDistance: 5,
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            agendaWeek: 'Semana',
            agendaDay: 'Día'
        },
        monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
        monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
        dayNames: ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'],
        dayNamesShort: ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'],
        dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','Sá'],
        allDayText: 'Todo el día',
        eventLimitText: function(n) {
            return '+' + n + ' más';
        },
        select: function(start, end) {
            openAddSessionModal(start.toDate(), end.toDate());
            window.wizardCalendar.fullCalendar('unselect');
        },
        dayClick: function(date) {
            var d = date.toDate();
            var start = new Date(d.getFullYear(), d.getMonth(), d.getDate(), 8, 0, 0);
            var end = new Date(start.getTime() + 2 * 3600 * 1000);
            openAddSessionModal(start, end);
        },
        events: function(start, end, timezone, callback) {
            var filterLocationId = $('#calendar-location-filter').val();
            var sessionEvents = wizardSessions.map(function(s) {
                if (filterLocationId && parseInt(s.location_id) !== parseInt(filterLocationId)) return null;
                var parts = s.session_date.split('-');
                var sParts = s.start_time.split(':');
                var eParts = s.end_time.split(':');
                var startDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]), parseInt(sParts[0]), parseInt(sParts[1]));
                var endDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]), parseInt(eParts[0]), parseInt(eParts[1]));
                return {
                    id: 'session-' + s.id,
                    title: s.location_name,
                    start: startDate,
                    end: endDate,
                    color: getLocationColor(s.location_id),
                    allDay: false
                };
            }).filter(function(e) { return e !== null; });
            var startStr = start.format('YYYY-MM-DD');
            var endStr = end.format('YYYY-MM-DD');
            var facilitatorId = $('#wizard-facilitador').val();
            $.ajax({
                url: '{{ url("u/af_programadas/blocked-slots") }}',
                type: 'GET',
                dataType: 'json',
                data: {
                    start: startStr,
                    end: endStr,
                    facilitator_id: facilitatorId || ''
                },
                success: function(blocked) {
                    blockedSlotsCache = [];
                    var fixedBlocked = (Array.isArray(blocked) ? blocked : []).map(function(e) {
                        var isFilteredMatch = filterLocationId && e.type === 'location' && parseInt(e.location_id) === parseInt(filterLocationId);
                        var isFilteredOut = filterLocationId && e.type === 'location' && parseInt(e.location_id) !== parseInt(filterLocationId);
                        if (isFilteredOut) return null;

                        var item = {
                            id: e.id,
                            title: isFilteredMatch ? e.title + ' (Ocupado)' : e.title,
                            start: new Date(e.start),
                            end: e.end ? new Date(e.end) : null,
                            color: isFilteredMatch ? getLocationColor(e.location_id) : e.color,
                            location_id: e.location_id,
                            type: e.type
                        };
                        if (!isFilteredMatch) {
                            item.rendering = 'background';
                        }
                        if (e.start) {
                            var startParts = e.start.split('T');
                            var endStr = e.end ? e.end.split('T')[1] : startParts[1];
                            blockedSlotsCache.push({
                                location_id: e.location_id,
                                facilitator_id: e.facilitator_id || null,
                                type: e.type || 'location',
                                session_date: startParts[0],
                                start_time: startParts[1] ? startParts[1].slice(0, 5) : '00:00',
                                end_time: endStr ? endStr.slice(0, 5) : '23:59',
                                title: e.title
                            });
                        }
                        return item;
                    }).filter(function(e) { return e !== null; });
                    var allEvents = sessionEvents.concat(fixedBlocked);
                    callback(allEvents);
                },
                error: function() {
                    blockedSlotsCache = [];
                    callback(sessionEvents);
                }
            });
        },
        eventRender: function(event, element) {
            element.attr('title', event.title);
            if (event.rendering === 'background') {
                if (event.type === 'facilitator') {
                    element.addClass('blocked-event-fac');
                } else {
                    element.addClass('blocked-event');
                }
            }
        }
    });
}

function refreshCalendarEvents() {
    if (!window.wizardCalendar) return;
    var currentDate = $('#wizard-calendar').fullCalendar('getDate');
    window.wizardCalendar.fullCalendar('destroy');
    initWizardCalendar();
    $('#wizard-calendar').fullCalendar('gotoDate', currentDate);
}

function renderLocationLegend() {
    var $legend = $('#location-color-legend');
    var filterLocationId = $('#calendar-location-filter').val();
    if (!filterLocationId) {
        $legend.empty().hide();
        return;
    }
    var color = getLocationColor(filterLocationId);
    var locationName = $('#calendar-location-filter option:selected').text();
    $legend.html(
        '<span style="display:inline-block;width:12px;height:12px;background:' + color + ';border-radius:2px;margin-right:4px;vertical-align:middle;"></span>' +
        '<span style="vertical-align:middle;">' + locationName + '</span>' +
        '<span style="margin-left:6px;color:#999;font-size:10px;">● Bloques = ocupado</span>'
    ).show();
}

function openNewSessionModal() {
    var now = new Date();
    now.setMinutes(Math.ceil(now.getMinutes() / 15) * 15, 0, 0);
    var end = new Date(now.getTime() + 2 * 3600 * 1000);
    openAddSessionModal(now, end);
}

function openAddSessionModal(start, end, editId) {
    $('#add-session-form')[0].reset();
    $('#add-session-location').val('').trigger('change');
    $('#add-session-edit-id').val('');
    clearFieldErrors('#add-session-form');
    clearInlineAlert('add-session-alert');
    overlapPendingConfirm = false;

    var filterLocationId = $('#calendar-location-filter').val();
    if (filterLocationId && !editId) {
        $('#add-session-location').val(filterLocationId).trigger('change');
    } else {
        $('#add-session-location').val('').trigger('change');
    }

    var pad = function(n) { return n < 10 ? '0' + n : '' + n; };
    var sh = pad(start.getHours()), sm = pad(start.getMinutes());
    var eh = pad(end.getHours()), em = pad(end.getMinutes());
    var sy = start.getFullYear(), smo = pad(start.getMonth()+1), sd = pad(start.getDate());
    $('#add-session-start').val(to12h(sh + ':' + sm));
    $('#add-session-end').val(to12h(eh + ':' + em));
    $('#add-session-date').val(sy + '-' + smo + '-' + sd);

    if (editId) {
        $('#add-session-edit-id').val(editId);
        $('#add-session-modal .modal-title').text('Editar Sesión');
    } else {
        $('#add-session-modal .modal-title').text('Agregar Sesión');
    }

    $('#add-session-modal').modal({ backdrop: false, keyboard: true });
    updateModalRemaining();
}

function openEditSessionModal(id) {
    var session = wizardSessions.find(function(s) { return s.id === id; });
    if (!session) return;

    var dateParts = session.session_date.split('-');
    var startDate = new Date(parseInt(dateParts[0]), parseInt(dateParts[1]) - 1, parseInt(dateParts[2]));
    var startParts = session.start_time.split(':');
    startDate.setHours(parseInt(startParts[0]), parseInt(startParts[1]));
    var endDate = new Date(startDate.getTime());
    var endParts = session.end_time.split(':');
    endDate.setHours(parseInt(endParts[0]), parseInt(endParts[1]));

    openAddSessionModal(startDate, endDate, id);
    setTimeout(function() {
        $('#add-session-location').val(session.location_id).trigger('change');
        $('#add-session-notes').val(session.notes || '');
    }, 100);
}

function saveSessionFromModal() {
    clearFieldErrors('#add-session-form');
    clearInlineAlert('add-session-alert');

    var locationId = $('#add-session-location').val();
    var locationName = $('#add-session-location option:selected').text();
    var sessionDate = $('#add-session-date').val();
    var startTime = to24h($('#add-session-start').val());
    var endTime = to24h($('#add-session-end').val());
    var notes = $('#add-session-notes').val();
    var editId = $('#add-session-edit-id').val();

    var invalid = false;
    if (!locationId) { setFieldError('#add-session-location', 'Seleccione una ubicación.'); invalid = true; }
    if (!sessionDate) { setFieldError('#add-session-date', 'Seleccione una fecha.'); invalid = true; }
    if (!startTime) { setFieldError('#add-session-start', 'Ingrese la hora de inicio.'); invalid = true; }
    if (!endTime) { setFieldError('#add-session-end', 'Ingrese la hora de fin.'); invalid = true; }
    if (invalid) return;

    if (timeToMinutes(startTime) >= timeToMinutes(endTime)) {
        setFieldError('#add-session-end', 'La hora de fin debe ser posterior a la hora de inicio.');
        return;
    }

    var durHours = computeDurationHours(startTime, endTime);

    var totalHours = durHours;
    wizardSessions.forEach(function(s) {
        if (editId && s.id === parseInt(editId)) return;
        totalHours += s.duration_hours;
    });
    if (totalHours > courseDuration) {
        showInlineAlert('add-session-alert', 'danger',
            'Excede la duración total del curso (' + courseDuration + ' horas). ' +
            'Restante: ' + (courseDuration - (totalHours - durHours)) + ' horas.');
        return;
    }

    var overlap = findBlockedOverlap(locationId, sessionDate, startTime, endTime);
    if (overlap && !overlapPendingConfirm) {
        overlapPendingConfirm = true;
        showInlineAlert('add-session-alert', 'warning',
            'Advertencia: "' + overlap.title + '" ya tiene una sesión programada en ese horario. ' +
            'Presione "Agregar" nuevamente para confirmar y continuar.');
        return;
    }
    overlapPendingConfirm = false;

    var facOverlap = findFacilitatorOverlap(sessionDate, startTime, endTime);
    if (facOverlap) {
        showInlineAlert('add-session-alert', 'danger',
            'El facilitador seleccionado ya tiene una sesión programada en este horario: ' + facOverlap.title + '.');
        return;
    }

    if (editId) {
        var idx = wizardSessions.findIndex(function(s) { return s.id === parseInt(editId); });
        if (idx >= 0) {
            wizardSessions[idx].location_id = locationId;
            wizardSessions[idx].location_name = locationName;
            wizardSessions[idx].session_date = sessionDate;
            wizardSessions[idx].start_time = startTime;
            wizardSessions[idx].end_time = endTime;
            wizardSessions[idx].duration_hours = durHours;
            wizardSessions[idx].notes = notes;
        }
    } else {
        sessionCounter++;
        wizardSessions.push({
            id: sessionCounter,
            location_id: locationId,
            location_name: locationName,
            session_date: sessionDate,
            start_time: startTime,
            end_time: endTime,
            duration_hours: durHours,
            notes: notes
        });
    }

    $('#add-session-modal').modal('hide');
    renderSessionTable();
    refreshCalendarEvents();
    if (getCurrentStep() === 3) {
        renderReviewPanel();
    }
}

function applyDurationChip(hours) {
    var startVal = to24h($('#add-session-start').val());
    if (!startVal) return;
    var startMin = timeToMinutes(startVal);
    var endMin = startMin + hours * 60;
    var eh = Math.floor(endMin / 60) % 24;
    var em = endMin % 60;
    var pad = function(n) { return n < 10 ? '0' + n : '' + n; };
    $('#add-session-end').val(to12h(pad(eh) + ':' + pad(em)));
    updateModalRemaining();
}

function updateModalRemaining() {
    var startVal = to24h($('#add-session-start').val());
    var endVal = to24h($('#add-session-end').val());
    var dur = 0;
    if (startVal && endVal && timeToMinutes(endVal) > timeToMinutes(startVal)) {
        dur = computeDurationHours(startVal, endVal);
    }
    var editId = $('#add-session-edit-id').val();
    var assigned = 0;
    wizardSessions.forEach(function(s) {
        if (editId && s.id === parseInt(editId)) return;
        assigned += s.duration_hours;
    });
    var remaining = courseDuration - assigned - dur;
    var $el = $('#modal-remaining');
    if (remaining < 0) {
        $el.html('<span class="text-danger"><strong>' + remaining + 'h</strong></span> restantes de ' + courseDuration + 'h');
    } else {
        $el.html('<strong>' + remaining + 'h</strong> restantes de ' + courseDuration + 'h');
    }
}

function to24h(time) {
    var m = String(time).match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!m) return time;
    var hour = parseInt(m[1], 10);
    var minute = m[2];
    var period = m[3].toUpperCase();
    if (period === 'PM' && hour !== 12) hour += 12;
    if (period === 'AM' && hour === 12) hour = 0;
    return ('0' + hour).slice(-2) + ':' + minute;
}

function to12h(time) {
    var parts = String(time).split(':');
    if (parts.length < 2) return time;
    var hour = parseInt(parts[0], 10);
    var minute = parts[1];
    var period = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12 || 12;
    return hour + ':' + minute + ' ' + period;
}

function timeToMinutes(time) {
    var parts = String(time).split(':');
    return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
}

function computeDurationHours(start, end) {
    var diff = timeToMinutes(end) - timeToMinutes(start);
    return Math.max(Math.round((diff / 60) * 4) / 4, 0);
}

function removeWizardSession(id) {
    wizardSessions = wizardSessions.filter(function(s) { return s.id !== id; });
    renderSessionTable();
    refreshCalendarEvents();
}

function formatTime12(t) {
    return to12h(t);
}

function renderSessionTable() {
    var $tbody = $('#wizard-sessions-tbody');
    $tbody.empty();
    $('#sessions-count-label').text(wizardSessions.length);
    var totalAssigned = 0;
    if (wizardSessions.length === 0) {
        $tbody.append('<tr><td colspan="6" class="text-center"><i class="entypo-calendar" style="font-size: 26px; display: block; margin-bottom: 6px;"></i>' +
            '<span class="text-muted">No hay sesiones agregadas.<br>Haga clic en una fecha del calendario o use "Agregar Sesión".</span></td></tr>');
    } else {
        wizardSessions.forEach(function(s, i) {
            totalAssigned += s.duration_hours;
            $tbody.append(
                '<tr>' +
                '<td class="text-center">' + (i + 1) + '</td>' +
                '<td>' + s.location_name + '</td>' +
                '<td>' + s.session_date + '</td>' +
                '<td>' + formatTime12(s.start_time) + '</td>' +
                '<td>' + formatTime12(s.end_time) + '</td>' +
                '<td class="text-center">' + s.duration_hours + ' hrs</td>' +
                '<td class="actions">' +
                    '<button type="button" class="btn btn-info btn-xs" onclick="openEditSessionModal(' + s.id + ')" title="Editar sesión"><i class="entypo-pencil"></i></button> ' +
                    '<button type="button" class="btn btn-danger btn-xs" onclick="removeWizardSession(' + s.id + ')" title="Eliminar sesión"><i class="entypo-trash"></i></button>' +
                '</td>' +
                '</tr>'
            );
        });
    }
    $('#sessions-summary').html(
        wizardSessions.length === 0
            ? '<span class="text-muted">Sin sesiones aún</span>'
            : '<strong>' + wizardSessions.length + '</strong> sesión(es) · <strong>' + totalAssigned + 'h</strong> de ' + courseDuration + 'h asignadas'
    ).attr('aria-live', 'polite');
    updateDurationBar();
}

function updateDurationBar() {
    var assigned = 0;
    wizardSessions.forEach(function(s) { assigned += s.duration_hours; });
    var remaining = courseDuration - assigned;
    var pct = courseDuration > 0 ? Math.min((assigned / courseDuration) * 100, 100) : 0;
    var barClass = pct >= 100 ? 'progress-bar-success' : pct > 50 ? 'progress-bar-info' : 'progress-bar-warning';
    if (pct > 75) barClass = 'progress-bar-info';
    if (pct >= 100) barClass = 'progress-bar-success';

    $('#duration-bar').css('width', pct + '%').attr('class', 'progress-bar ' + barClass);
    $('#duration-bar-text').text(Math.round(pct) + '%');
    if (remaining > 0) {
        $('#duration-status').html('<span class="text-warning"><strong>Horas restantes: ' + remaining + ' horas</strong></span>');
    } else if (remaining === 0) {
        $('#duration-status').html('<span class="text-success"><strong>Todas las horas han sido asignadas</strong></span>');
    } else {
        $('#duration-status').html('<span class="text-danger"><strong>Excedido por ' + Math.abs(remaining) + ' horas</strong></span>');
    }

    var $submitBtn = $('#wizard-btn-submit');
    if (assigned === courseDuration && wizardSessions.length > 0) {
        $submitBtn.prop('disabled', false);
    } else {
        $submitBtn.prop('disabled', true);
    }
}

function renderReviewPanel() {
    var $tbody = $('#review-sessions-tbody');
    $tbody.empty();
    var totalHours = 0;
    if (wizardSessions.length === 0) {
        $tbody.append('<tr><td colspan="8" class="text-center text-muted"><i class="entypo-calendar" style="font-size: 22px; display: block; margin-bottom: 6px;"></i>No hay sesiones para revisar.</td></tr>');
    }
    wizardSessions.forEach(function(s, i) {
        totalHours += s.duration_hours;
        $tbody.append(
            '<tr>' +
            '<td>' + (i + 1) + '</td>' +
            '<td>' + s.location_name + '</td>' +
            '<td>' + s.session_date + '</td>' +
            '<td>' + formatTime12(s.start_time) + '</td>' +
            '<td>' + formatTime12(s.end_time) + '</td>' +
            '<td>' + s.duration_hours + ' hrs</td>' +
            '<td>' + (s.notes || '-') + '</td>' +
            '<td class="actions" style="white-space: nowrap;">' +
                '<button type="button" class="btn btn-info btn-xs" onclick="openEditSessionModal(' + s.id + ')" title="Editar sesión"><i class="entypo-pencil"></i></button> ' +
                '<button type="button" class="btn btn-danger btn-xs" onclick="removeReviewSession(' + s.id + ')" title="Eliminar sesión"><i class="entypo-trash"></i></button>' +
            '</td>' +
            '</tr>'
        );
    });
    $('#review-course-title').text($('#wizard-titulo option:selected').text());
    $('#review-facilitador').text($('#wizard-facilitador option:selected').text());
    $('#review-total-hours').text(totalHours + ' / ' + courseDuration + ' horas');
    $('#review-sessions-count').text(wizardSessions.length);

    updateDurationBar();

    $('#review-duration-bar').css('width', (courseDuration > 0 ? Math.min((totalHours / courseDuration) * 100, 100) : 0) + '%');
}

function removeReviewSession(id) {
    wizardSessions = wizardSessions.filter(function(s) { return s.id !== id; });
    renderReviewPanel();
    renderSessionTable();
    refreshCalendarEvents();
}

function submitWizardSchedule() {
    clearInlineAlert('wizard-submit-alert');
    if (wizardSessions.length === 0) {
        showInlineAlert('wizard-submit-alert', 'warning', 'Debe agregar al menos una sesión antes de programar.');
        return;
    }

    var totalAssigned = 0;
    wizardSessions.forEach(function(s) { totalAssigned += s.duration_hours; });
    if (totalAssigned > courseDuration) {
        showInlineAlert('wizard-submit-alert', 'danger', 'Las sesiones exceden la duración del curso (' + courseDuration + ' horas).');
        return;
    }

    var payload = {
        titulo: $('#wizard-titulo').val(),
        facilitador: $('#wizard-facilitador').val(),
        sessions: wizardSessions.map(function(s) {
            return {
                location_id: s.location_id,
                session_date: s.session_date,
                start_time: s.start_time,
                end_time: s.end_time,
                notes: s.notes || ''
            };
        })
    };

    var $submitBtn = $('#wizard-btn-submit');
    $submitBtn.prop('disabled', true);
    $submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Programando...');

    $.ajax({
        url: '{{ url("u/af_programadas/schedule") }}',
        type: 'POST',
        data: JSON.stringify(payload),
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            window.location.href = '{{ url("u/af_programadas") }}';
        },
        error: function(xhr) {
            $submitBtn.prop('disabled', false);
            $submitBtn.html('<i class="fa fa-check"></i> Programar');
            var msg = 'Error al programar el curso.';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                var errs = [];
                $.each(xhr.responseJSON.errors, function(k, v) {
                    errs.push(v.join(' '));
                });
                msg = errs.join('<br>');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            showInlineAlert('wizard-submit-alert', 'danger', msg);
        }
    });
}
</script>
@endpush

<div class="modal fade" id="wizard-modal" tabindex="-1" role="dialog" aria-labelledby="wizard-label">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="wizard-label">Programar Acción de Formación</h4>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>

            <ul class="wizard-steps" style="margin: 8px 20px 0;">
                <li class="active" data-step="1" onclick="wizardStepClick(1)" title="Ir a Datos Generales">
                    <span class="step-number">1</span>
                    <span class="step-label">Datos Generales</span>
                </li>
                <li data-step="2" onclick="wizardStepClick(2)" title="Ir a Sesiones">
                    <span class="step-number">2</span>
                    <span class="step-label">Sesiones</span>
                </li>
                <li data-step="3">
                    <span class="step-number">3</span>
                    <span class="step-label">Revisar y Confirmar</span>
                </li>
            </ul>

            <form class="form-horizontal hidden" method="POST" id="wizard-form" enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">

                <div class="modal-body">
                    <div id="wizard-alert"></div>
                    {{-- STEP 1: General Data --}}
                    <div class="wizard-step-panel active" id="panel-step1">
                        <div class="form-group">
                            <div class="col-lg-12 col-md-12">
                                <label for="wizard-titulo">Acción de Formación</label>
                                <select name="titulo" id="wizard-titulo" class="form-control" required>
                                    <option></option>
                                    @isset($categoriasAcciones)
                                        @foreach($categoriasAcciones as $category)
                                            <optgroup label="{{ $category->name }}">
                                                @foreach($category->courses as $af)
                                                    <option value="{{ $af->id }}" data-duration="{{ $af->duration }}">{{ $af->title }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-lg-12 col-md-12">
                                <label for="wizard-facilitador">Facilitador</label>
                                <select name="facilitador" id="wizard-facilitador" class="form-control" required>
                                    <option></option>
                                    @foreach($facilitadores as $facilitador)
                                        <option value="{{ $facilitador->person->facilitator->id }}">{{ $facilitador->person->name }} {{ $facilitador->person->last_name }} C.I:{{ $facilitador->person->id_type_id == 1 ? 'V' : 'E' }}-{{ $facilitador->person->id_format() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-lg-12 col-md-12">
                                <p id="duration-info" class="text-info"></p>
                            </div>
                        </div>
                    </div>

                    {{-- STEP 2: Calendar Sessions (side-by-side layout) --}}
                    <div class="wizard-step-panel" id="panel-step2">
                        <div class="row" style="margin-bottom: 10px;">
                            <div class="col-md-12">
                                <div class="progress duration-progress">
                                    <div id="duration-bar" class="progress-bar" role="progressbar" style="width: 0%">
                                        <span id="duration-bar-text">0%</span>
                                    </div>
                                </div>
                                <div id="duration-status" style="margin-top: 5px;"></div>
                            </div>
                        </div>
                        <div class="step2-layout">
                            <div class="step2-calendar">
                                <p class="text-muted" style="margin-bottom: 8px;">Seleccione un rango de fechas/horas en el calendario para agregar una sesión.</p>
                                <div class="calendar-env">
                                    <div id="wizard-calendar"></div>
                                </div>
                            </div>
                            <div class="step2-sidebar">
                                <div class="step2-sidebar-header">
                                    <div style="margin-bottom: 10px;">
                                        <label style="font-size: 12px; font-weight: 600; margin-bottom: 4px;">Filtrar por ubicación</label>
                                        <select id="calendar-location-filter" class="form-control input-sm">
                                            <option value="">Todas las ubicaciones</option>
                                            @php
                                                $groupedFilterLocations = $locations->groupBy(function ($location) {
                                                    $building = $location->floor->building->name ?? 'Sin edificio';
                                                    $floor = $location->floor->name ?? 'Sin piso';
                                                    return $building . ' > ' . $floor;
                                                });
                                            @endphp
                                            @foreach($groupedFilterLocations as $group => $groupLocations)
                                                <optgroup label="{{ $group }}">
                                                    @foreach($groupLocations as $location)
                                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div id="location-color-legend" style="margin-bottom: 10px; font-size: 11px; display: none;"></div>
                                    <div style="margin-bottom: 12px;">
                                        <button type="button" class="btn btn-primary btn-sm btn-block" onclick="openNewSessionModal()">
                                            <i class="entypo-plus"></i> Agregar Sesión
                                        </button>
                                    </div>
                                    <h5>Sesiones (<span id="sessions-count-label">0</span>)</h5>
                                    <div id="sessions-summary" class="text-muted" style="margin-bottom: 6px; font-size: 12px;"></div>
                                </div>
                                <div class="step2-sessions-scroll">
                                    <table class="table table-striped table-bordered sessions-table">
                                        <thead>
                                            <tr>
                                                <th class="text-center">#</th>
                                                <th>Ubicación</th>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th class="text-center">Dur.</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="wizard-sessions-tbody">
                                            <tr><td colspan="6" class="text-muted text-center">No hay sesiones agregadas.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- STEP 3: Review --}}
                    <div class="wizard-step-panel" id="panel-step3">
                        <div id="wizard-submit-alert"></div>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Acción de Formación:</strong> <span id="review-course-title"></span></p>
                                <p><strong>Facilitador:</strong> <span id="review-facilitador"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Total Sesiones:</strong> <span id="review-sessions-count"></span></p>
                                <p><strong>Horas:</strong> <span id="review-total-hours"></span></p>
                            </div>
                        </div>
                        <div class="progress duration-progress">
                            <div id="review-duration-bar" class="progress-bar progress-bar-info" role="progressbar" style="width: 0%"></div>
                        </div>
                        <br>
                        <table class="table table-striped table-bordered table-center review-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Ubicación</th>
                                    <th>Fecha</th>
                                    <th>Hora Inicio</th>
                                    <th>Hora Fin</th>
                                    <th>Duración</th>
                                    <th>Notas</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="review-sessions-tbody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer" style="text-align: center;">
                    <button type="button" class="btn btn-default" id="wizard-btn-prev" onclick="wizardPrev(getCurrentStep())" style="display:none;">
                        <i class="fa fa-arrow-left"></i> Anterior
                    </button>
                    <button type="button" class="btn btn-primary" id="wizard-btn-next" onclick="wizardNext(getCurrentStep())">
                        Siguiente <i class="fa fa-arrow-right"></i>
                    </button>
                    <button type="button" class="btn btn-success" id="wizard-btn-submit" onclick="submitWizardSchedule()" style="display:none;">
                        <i class="fa fa-check"></i> Programar
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Session Modal (triggered from calendar select or add button) --}}
<div class="modal fade" id="add-session-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Agregar Sesión</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="add-session-edit-id" value="">
                <div id="add-session-alert"></div>
                <form id="add-session-form">
                <div class="form-group">
                    <div class="col-lg-12 col-md-12">
                        <label for="add-session-location">Ubicación</label>
                        <select id="add-session-location" class="form-control" required>
                            <option></option>
                            @php
                                $groupedLocations = $locations->groupBy(function ($location) {
                                    $building = $location->floor->building->name ?? 'Sin edificio';
                                    $floor = $location->floor->name ?? 'Sin piso';
                                    return $building . ' > ' . $floor;
                                });
                            @endphp
                            @foreach($groupedLocations as $group => $groupLocations)
                                <optgroup label="{{ $group }}">
                                    @foreach($groupLocations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-12 col-md-12">
                        <label for="add-session-date">Fecha</label>
                        <input type="text" id="add-session-date" class="form-control dat2" required autocomplete="off" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-6 col-md-6">
                        <label for="add-session-start">Hora Inicio</label>
                        <input type="text" id="add-session-start" class="form-control timepicker" required data-show-meridian="true" data-default-time="false" data-minute-step="15" aria-label="Hora de inicio">
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <label for="add-session-end">Hora Fin</label>
                        <input type="text" id="add-session-end" class="form-control timepicker" required data-show-meridian="true" data-default-time="false" data-minute-step="15" aria-label="Hora de fin">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-12 col-md-12">
                        <div class="duration-chips">
                            <span class="text-muted">Duración rápida:</span>
                            <button type="button" class="btn btn-xs btn-default" onclick="applyDurationChip(1)">1h</button>
                            <button type="button" class="btn btn-xs btn-default" onclick="applyDurationChip(2)">2h</button>
                            <button type="button" class="btn btn-xs btn-default" onclick="applyDurationChip(3)">3h</button>
                            <button type="button" class="btn btn-xs btn-default" onclick="applyDurationChip(4)">4h</button>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-12 col-md-12">
                        <label for="add-session-notes">Notas</label>
                        <textarea id="add-session-notes" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-12 col-md-12">
                        <p id="modal-remaining" class="text-muted" aria-live="polite"></p>
                    </div>
                </div>
                </form>
            </div>
            <div class="modal-footer" style="text-align: center;">
                <button type="button" class="btn btn-primary" onclick="saveSessionFromModal()">Agregar</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
function getCurrentStep() {
    if ($('#panel-step1').hasClass('active')) return 1;
    if ($('#panel-step2').hasClass('active')) return 2;
    if ($('#panel-step3').hasClass('active')) return 3;
    return 1;
}

$('#add-session-modal').on('shown.bs.modal', function() {
    $('#add-session-location').select2({ allowClear: true, placeholder: 'Seleccionar ubicación', dropdownParent: $('#add-session-modal') });
    $('.dat2', $(this)).datepicker({ format: "yyyy-mm-dd", todayHighlight: true });
    $('.timepicker', $(this)).timepicker({ showMeridian: true, defaultTime: false, minuteStep: 15 });
    var sel = $('#add-session-location').data('select2');
    if (sel && sel.focusser) {
        setTimeout(function() { sel.focusser.focus(); }, 50);
    }
});

$('#add-session-modal').on('hidden.bs.modal', function() {
    $('#add-session-location').select2('destroy');
    $('.modal-backdrop').not('.in').remove();
    if ($('#wizard-modal').hasClass('in')) {
        $('body').addClass('modal-open');
    }
});

$('#add-session-start, #add-session-end, #add-session-date').on('keydown change', function() {
    updateModalRemaining();
});

$('#add-session-form').on('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && !$(e.target).hasClass('select2-focusser')) {
        e.preventDefault();
        saveSessionFromModal();
    }
});

$('#wizard-modal').on('shown.bs.modal', function() {
    $(".loader").addClass("hidden");
    $("#wizard-form").removeClass("hidden");
    clearInlineAlert('wizard-alert');
    setTimeout(function() {
        var sel = $('#wizard-titulo').data('select2');
        if (sel && sel.focusser) {
            sel.focusser.focus();
        }
    }, 100);
});

$('#wizard-modal').on('hidden.bs.modal', function() {
    if (window.wizardCalendar) {
        window.wizardCalendar.fullCalendar('destroy');
        window.wizardCalendar = null;
    }
});
</script>
