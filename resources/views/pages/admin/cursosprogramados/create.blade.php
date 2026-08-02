@push('CSS')
<style>
    .wizard-steps { list-style: none; padding: 0; margin: 0 0 20px; display: flex; justify-content: center; gap: 0; }
    .wizard-steps li { flex: 1; text-align: center; position: relative; padding: 10px 0; }
    .wizard-steps li .step-number { display: inline-block; width: 32px; height: 32px; line-height: 32px; border-radius: 50%; background: #ddd; color: #999; font-weight: bold; margin-bottom: 5px; }
    .wizard-steps li.active .step-number { background: #3498db; color: #fff; }
    .wizard-steps li.completed .step-number { background: #27ae60; color: #fff; }
    .wizard-steps li .step-label { display: block; font-size: 12px; color: #999; }
    .wizard-steps li.active .step-label { color: #333; font-weight: bold; }
    .wizard-steps li.completed .step-label { color: #27ae60; }
    .wizard-steps li::after { content: ''; position: absolute; top: 25px; left: 50%; width: 100%; height: 2px; background: #ddd; z-index: -1; }
    .wizard-steps li:last-child::after { display: none; }
    .wizard-steps li.completed::after { background: #27ae60; }
    .wizard-steps li.active::after { background: #3498db; }
    .wizard-step-panel { display: none; }
    .wizard-step-panel.active { display: block; }
    .session-row { background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 4px; padding: 12px; margin-bottom: 10px; position: relative; }
    .session-row .remove-session { position: absolute; top: 8px; right: 8px; }
    .duration-progress { margin-top: 10px; }
    #wizard-calendar { min-height: 400px; }
    .fc-event { cursor: pointer; }
    .blocked-event { background: #999 !important; border-color: #999 !important; cursor: not-allowed !important; }
    .review-table td { vertical-align: middle !important; }
    #duration-warning { display: none; }
    .modal-xl { width: 90%; max-width: 900px; }
</style>
@endpush

@push('JS')
<script>
var wizardSessions = [];
var courseDuration = 0;
var sessionCounter = 0;

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
        var catData = selected.closest('optgroup').data('duration');
        courseDuration = parseInt(selected.data('duration')) || 0;
        $('#duration-info').text('Duración del curso: ' + courseDuration + ' horas');
        updateDurationBar();
    });

    $('#wizard-facilitador').select2({ allowClear: true, placeholder: 'Seleccionar facilitador' });
}

function resetWizard() {
    $('#step1').addClass('active');
    $('#step2, #step3').removeClass('active');
    $('#panel-step1').addClass('active');
    $('#panel-step2, #panel-step3').removeClass('active');
    $('.wizard-steps li').removeClass('active completed');
    $('.wizard-steps li:eq(0)').addClass('active');
    wizardSessions = [];
    renderSessionList();
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

    if (step === 2) {
        initWizardCalendar();
    } else if (step === 3) {
        renderReviewPanel();
    }
}

function wizardNext(step) {
    if (step === 1) {
        if (!$('#wizard-titulo').val()) {
            alert('Debe seleccionar una acción de formación.');
            return;
        }
        if (!$('#wizard-facilitador').val()) {
            alert('Debe seleccionar un facilitador.');
            return;
        }
        courseDuration = parseInt($('#wizard-titulo option:selected').data('duration')) || 0;
        $('#duration-info').text('Duración del curso: ' + courseDuration + ' horas');
        updateDurationBar();
    }
    if (step === 2 && wizardSessions.length === 0) {
        alert('Debe agregar al menos una sesión.');
        return;
    }
    wizardGoToStep(step + 1);
}

function wizardPrev(step) {
    wizardGoToStep(step - 1);
}

function initWizardCalendar() {
    if (window.wizardCalendar) {
        window.wizardCalendar.fullCalendar('destroy');
    }

    window.wizardCalendar = $('#wizard-calendar').fullCalendar({
        header: { left: 'prev,next today', center: 'title', right: 'month,agendaWeek,agendaDay' },
        defaultView: 'agendaWeek',
        slotMinutes: 15,
        allDaySlot: false,
        minTime: '06:00:00',
        maxTime: '22:00:00',
        selectable: true,
        selectHelper: true,
        select: function(start, end) {
            openAddSessionModal(start, end);
            window.wizardCalendar.fullCalendar('unselect');
        },
        events: function(start, end, callback) {
            var events = wizardSessions.map(function(s) {
                return {
                    id: 'session-' + s.id,
                    title: s.location_name,
                    start: s.session_date + 'T' + s.start_time,
                    end: s.session_date + 'T' + s.end_time,
                    color: '#5bc0de'
                };
            });
            $.get('{{ url("u/af_programadas/blocked-slots") }}', {
                start: start.format('YYYY-MM-DD'),
                end: end.format('YYYY-MM-DD')
            }, function(blocked) {
                events = events.concat(blocked);
                callback(events);
            });
        },
        eventRender: function(event, element) {
            element.attr('title', event.title);
            if (event.rendering === 'background') {
                element.addClass('blocked-event');
            }
        }
    });
}

function openAddSessionModal(start, end) {
    $('#add-session-form')[0].reset();
    $('#add-session-location').val('').trigger('change');
    $('#add-session-start').val(start.format('HH:mm'));
    $('#add-session-end').val(end.format('HH:mm'));
    $('#add-session-date').val(start.format('YYYY-MM-DD'));
    $('#add-session-modal').modal('show');
    $('#add-session-location').select2({ allowClear: true, placeholder: 'Seleccionar ubicación' });
}

function saveSessionFromModal() {
    var locationId = $('#add-session-location').val();
    var locationName = $('#add-session-location option:selected').text();
    var sessionDate = $('#add-session-date').val();
    var startTime = $('#add-session-start').val();
    var endTime = $('#add-session-end').val();
    var notes = $('#add-session-notes').val();

    if (!locationId || !sessionDate || !startTime || !endTime) {
        alert('Todos los campos obligatorios deben ser completados.');
        return;
    }

    if (startTime >= endTime) {
        alert('La hora de fin debe ser posterior a la hora de inicio.');
        return;
    }

    var durHours = computeDurationHours(startTime, endTime);

    var totalHours = durHours;
    wizardSessions.forEach(function(s) { totalHours += s.duration_hours; });
    if (totalHours > courseDuration) {
        alert('Excede la duración total del curso (' + courseDuration + ' horas). Restante: ' + (courseDuration - (totalHours - durHours)) + ' horas.');
        return;
    }

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

    $('#add-session-modal').modal('hide');
    renderSessionList();
    if (window.wizardCalendar) {
        window.wizardCalendar.fullCalendar('refetchEvents');
    }
}

function computeDurationHours(start, end) {
    var parts = start.split(':');
    var sh = parseInt(parts[0]), sm = parseInt(parts[1]);
    parts = end.split(':');
    var eh = parseInt(parts[0]), em = parseInt(parts[1]);
    var diff = (eh * 60 + em) - (sh * 60 + sm);
    return Math.round((diff / 60) * 4) / 4;
}

function removeWizardSession(id) {
    wizardSessions = wizardSessions.filter(function(s) { return s.id !== id; });
    renderSessionList();
    if (window.wizardCalendar) {
        window.wizardCalendar.fullCalendar('refetchEvents');
    }
}

function renderSessionList() {
    var $list = $('#wizard-sessions-list');
    $list.empty();
    if (wizardSessions.length === 0) {
        $list.append('<p class="text-muted">No hay sesiones agregadas. Seleccione fechas en el calendario.</p>');
    } else {
        wizardSessions.forEach(function(s) {
            $list.append(
                '<div class="session-row">' +
                '<button type="button" class="btn btn-danger btn-xs remove-session" onclick="removeWizardSession(' + s.id + ')"><i class="entypo-trash"></i></button>' +
                '<strong>' + s.location_name + '</strong><br>' +
                '<small>' + s.session_date + ' | ' + s.start_time + ' - ' + s.end_time + ' (' + s.duration_hours + ' hrs)</small>' +
                (s.notes ? '<br><small class="text-muted">' + s.notes + '</small>' : '') +
                '</div>'
            );
        });
    }
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
}

function renderReviewPanel() {
    var $tbody = $('#review-sessions-tbody');
    $tbody.empty();
    var totalHours = 0;
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

function formatTime12(t) {
    var parts = t.split(':');
    var h = parseInt(parts[0]), m = parts[1];
    var ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + m + ' ' + ampm;
}

function submitWizardSchedule() {
    if (wizardSessions.length === 0) {
        alert('Debe agregar al menos una sesión.');
        return;
    }

    var totalAssigned = 0;
    wizardSessions.forEach(function(s) { totalAssigned += s.duration_hours; });
    if (totalAssigned > courseDuration) {
        alert('Las sesiones exceden la duración del curso.');
        return;
    }

    var payload = {
        titulo: $('#wizard-titulo').val(),
        facilitador: $('#wizard-facilitador').val(),
        sessions: wizardSessions.map(function(s) {
            return {
                location_id: s.location_id,
                session_date: s.session_date,
                start_time: s.start_time + ':00',
                end_time: s.end_time + ':00',
                notes: s.notes || ''
            };
        })
    };

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
            $('#wizard-modal').modal('hide');
            window.location.reload();
        },
        error: function(xhr) {
            var msg = 'Error al programar el curso.';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                var errs = [];
                $.each(xhr.responseJSON.errors, function(k, v) {
                    errs.push(v.join(' '));
                });
                msg = errs.join('\n');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            alert(msg);
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

            <ul class="wizard-steps" style="margin: 15px 20px 0;">
                <li class="active">
                    <span class="step-number">1</span>
                    <span class="step-label">Datos Generales</span>
                </li>
                <li>
                    <span class="step-number">2</span>
                    <span class="step-label">Sesiones</span>
                </li>
                <li>
                    <span class="step-number">3</span>
                    <span class="step-label">Revisar y Confirmar</span>
                </li>
            </ul>

            <form class="form-horizontal hidden" method="POST" id="wizard-form" enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">

                <div class="modal-body">
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

                    {{-- STEP 2: Calendar Sessions --}}
                    <div class="wizard-step-panel" id="panel-step2">
                        <div class="row">
                            <div class="col-md-12">
                                <p class="text-muted">Seleccione un rango de fechas/horas en el calendario para agregar una sesión.</p>
                                <div id="wizard-calendar"></div>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 15px;">
                            <div class="col-md-8">
                                <h5>Sesiones Agregadas</h5>
                                <div id="wizard-sessions-list">
                                    <p class="text-muted">No hay sesiones agregadas.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h5>Control de Duración</h5>
                                <div class="progress duration-progress">
                                    <div id="duration-bar" class="progress-bar" role="progressbar" style="width: 0%">
                                        <span id="duration-bar-text">0%</span>
                                    </div>
                                </div>
                                <div id="duration-status"></div>
                            </div>
                        </div>
                    </div>

                    {{-- STEP 3: Review --}}
                    <div class="wizard-step-panel" id="panel-step3">
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

{{-- Add Session Modal (triggered from calendar select) --}}
<div class="modal fade" id="add-session-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Agregar Sesión</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="col-lg-12 col-md-12">
                        <label for="add-session-location">Ubicación</label>
                        <select id="add-session-location" class="form-control" required>
                            <option></option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
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
                        <input type="text" id="add-session-start" class="form-control timepicker" required data-show-meridian="true" data-default-time="false" data-minute-step="15">
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <label for="add-session-end">Hora Fin</label>
                        <input type="text" id="add-session-end" class="form-control timepicker" required data-show-meridian="true" data-default-time="false" data-minute-step="15">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-12 col-md-12">
                        <label for="add-session-notes">Notas</label>
                        <textarea id="add-session-notes" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                </div>
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
});

$('#add-session-modal').on('hidden.bs.modal', function() {
    $('#add-session-location').select2('destroy');
});

$('#wizard-modal').on('shown.bs.modal', function() {
    $(".loader").addClass("hidden");
    $("#wizard-form").removeClass("hidden");
});

$('#wizard-modal').on('hidden.bs.modal', function() {
    if (window.wizardCalendar) {
        window.wizardCalendar.fullCalendar('destroy');
        window.wizardCalendar = null;
    }
});
</script>
