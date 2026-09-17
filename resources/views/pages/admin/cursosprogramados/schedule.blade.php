@extends('layouts.admin')

@push('CSS')
<link rel="stylesheet" href="{{url('assets/js/fullcalendar-2/fullcalendar.min.css')}}">
<style>
    .wizard-steps { list-style: none; padding: 0; margin: 0 0 8px; display: flex; justify-content: center; gap: 0; }
    .wizard-steps li { flex: 1; text-align: center; position: relative; padding: 4px 0; }
    .wizard-steps li .step-number { display: inline-block; width: 20px; height: 20px; line-height: 20px; font-size: 12px; border-radius: 50%; background: #ddd; color: #999; font-weight: bold; margin-bottom: 2px; }
    .wizard-steps li.active .step-number { background: #3498db; color: #fff; }
    .wizard-steps li.completed .step-number { background: #27ae60; color: #fff; }
    .wizard-steps li .step-label { display: block; font-size: 11px; color: #999; }
    .wizard-steps li.active .step-label { color: #333; font-weight: bold; }
    .wizard-steps li.completed .step-label { color: #27ae60; }
    .wizard-steps li::after { content: ''; position: absolute; top: 14px; left: 50%; width: 100%; height: 2px; background: #ddd; z-index: -1; }
    .wizard-steps li:last-child::after { display: none; }
    .wizard-steps li.completed::after { background: #27ae60; }
    .wizard-steps li.active::after { background: #3498db; }
    .wizard-step-panel { display: none; }
    .wizard-step-panel.active { display: block; }
    #wizard-calendar { height: 100%; }
    .fc-event { cursor: pointer; }
    .blocked-event { background: #999 !important; border-color: #999 !important; cursor: not-allowed !important; }
    .fc-bgevent { pointer-events: none !important; }
    .blocked-event-fac { background: #e67e22 !important; border-color: #e67e22 !important; cursor: not-allowed !important; background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, rgba(255,255,255,0.15) 5px, rgba(255,255,255,0.15) 10px) !important; }
    .review-table td { vertical-align: middle !important; }
    #duration-warning { display: none; }
    .step2-layout { display: flex; gap: 20px; height: calc(90vh - 250px); min-height: 400px; }
    .step2-calendar { flex: 3; min-width: 0; display: flex; flex-direction: column; }
    .step2-calendar .calendar-env { flex: 1; min-height: 0; }
    .step2-sidebar { flex: 2; min-width: 280px; display: flex; flex-direction: column; overflow: hidden; }
    .step2-sidebar-header { flex-shrink: 0; margin-bottom: 12px; }
    .step2-sessions-scroll { flex: 1; overflow-y: auto; min-height: 0; max-height: 380px; }
    .sessions-table { font-size: 13px; width: 100%; table-layout: fixed; }
    .sessions-table td { vertical-align: middle !important; padding: 8px 8px; overflow: hidden; }
    .sessions-header { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin: 2px 0 6px; }
    .sessions-header h5 { margin: 0; font-size: 13px; }
    .mini-budget { height: 6px; margin: 0 0 8px; border-radius: 3px; background: #e9ecef; }
    .review-summary {
        display: flex; align-items: center; justify-content: space-between; gap: 16px;
        margin-bottom: 10px; padding: 8px 12px;
        background: #f7f9fb; border: 1px solid #eef1f4; border-radius: 4px;
        font-size: 13px;
    }
    .review-summary .review-budget { flex: 0 0 200px; margin: 0; }
    .session-row-main { display: flex; align-items: center; gap: 6px; }
    .session-row-location { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; }
    .session-row-actions { flex-shrink: 0; white-space: nowrap; }
    .session-row-actions .btn { padding: 2px 6px; }
    .session-row-meta { font-size: 11px; color: #777; margin-top: 3px; }
    .sessions-table .actions .btn { padding: 2px 6px; }
    .step2-layout { height: auto; min-height: 500px; }
    .schedule-page .panel-body { padding: 20px; }
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
        border: 1px solid #e3e8ed;
        border-radius: 4px;
        -webkit-box-shadow: 0 12px 40px rgba(35, 45, 55, 0.16);
        -moz-box-shadow: 0 12px 40px rgba(35, 45, 55, 0.16);
        box-shadow: 0 12px 40px rgba(35, 45, 55, 0.16);
    }
    #add-session-modal .modal-header {
        padding: 16px 22px;
        border-bottom: 1px solid #eef1f4;
    }
    #add-session-modal .modal-title {
        font-weight: 600;
        font-size: 16px;
        color: #2b333b;
    }
    #add-session-modal .modal-body {
        padding: 20px 22px;
    }
    #add-session-modal .modal-footer {
        padding: 14px 22px;
        border-top: 1px solid #eef1f4;
        text-align: right;
    }
    #add-session-modal .form-group { margin-bottom: 14px; }
    #add-session-modal .form-control {
        height: 38px;
        border: 1px solid #d5dbe1;
        border-radius: 3px;
        -webkit-box-shadow: none;
        box-shadow: none;
        -webkit-transition: border-color 0.15s ease, box-shadow 0.15s ease;
        -moz-transition: border-color 0.15s ease, box-shadow 0.15s ease;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    #add-session-modal .form-control:focus {
        border-color: #9aa7b2;
        -webkit-box-shadow: 0 0 0 2px rgba(43, 51, 59, 0.08);
        -moz-box-shadow: 0 0 0 2px rgba(43, 51, 59, 0.08);
        box-shadow: 0 0 0 2px rgba(43, 51, 59, 0.08);
    }
    #add-session-modal label.control-label {
        font-size: 12px;
        font-weight: 600;
        color: #5b6672;
        margin-bottom: 5px;
    }
    #add-session-modal .session-section {
        padding: 0 0 16px;
        margin-bottom: 16px;
        border-bottom: 1px dashed #e9edf1;
    }
    #add-session-modal .session-section:last-of-type {
        padding-bottom: 0;
        margin-bottom: 0;
        border-bottom: 0;
    }
    #add-session-modal .session-section-title {
        display: block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.09em;
        text-transform: uppercase;
        color: #8a97a3;
        margin-bottom: 12px;
    }
    #add-session-modal .session-messages { margin-bottom: 16px; }
    #add-session-modal .session-messages:empty { display: none; }

    /* Duration */
    #add-session-modal .duration-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    #add-session-modal .duration-label { font-size: 12px; font-weight: 600; color: #5b6672; }
    #add-session-modal .duration-chips { display: inline-flex; gap: 6px; }
    #add-session-modal .duration-chips .btn {
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        padding: 5px 14px;
        border: 1px solid #d5dbe1;
        background: #fff;
        color: #5b6672;
        -webkit-transition: all 0.15s ease;
        -moz-transition: all 0.15s ease;
        transition: all 0.15s ease;
    }
    #add-session-modal .duration-chips .btn:hover {
        border-color: #00a651;
        color: #00a651;
    }
    #add-session-modal .duration-chips .btn.active,
    #add-session-modal .duration-chips .btn.active:hover {
        background: #00a651;
        border-color: #00a651;
        color: #fff;
    }
    #add-session-modal #modal-remaining {
        margin-left: auto;
        font-size: 12px;
        color: #5b6672;
        background: #f5f7f9;
        border: 1px solid #e9edf1;
        border-radius: 20px;
        padding: 5px 12px;
    }

    /* Repetition */
    #add-session-modal .repeat-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 13px; color: #5b6672; }
    #add-session-modal .repeat-row .form-control {
        width: 72px;
        height: 34px;
        display: inline-block;
        text-align: center;
    }
    #add-session-modal .repeat-help { font-size: 11px; color: #97a2ad; margin: 8px 0 0; }

    /* Callouts (validation / conflicts / gate hints) */
    .session-callout {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px 14px;
        border: 1px solid transparent;
        border-radius: 4px;
        font-size: 13px;
        line-height: 1.45;
        margin-bottom: 10px;
    }
    .session-callout:last-child { margin-bottom: 0; }
    .session-callout .callout-icon { font-size: 15px; line-height: 1.35; flex-shrink: 0; }
    .session-callout .callout-body { flex: 1; min-width: 0; }
    .session-callout .callout-title { display: block; font-weight: 700; margin-bottom: 2px; }
    .session-callout ul { margin: 4px 0 0; padding-left: 16px; }
    .session-callout li { margin-bottom: 2px; }
    .session-callout--warning { background: #fff8e6; border-color: #f2d99a; color: #7a5b12; }
    .session-callout--danger { background: #fdf0f0; border-color: #f0c2c2; color: #8a2b2b; }
    .session-callout--info { background: #eef5fb; border-color: #cfe0ef; color: #2c5b7a; }
    .session-callout--success { background: #eef8f1; border-color: #c3e6cf; color: #276b42; }
    #submit-gate-hint .session-callout { margin-bottom: 0; }

    /* Context line (course · facilitator · status) */
    .schedule-context { margin: -2px 0 10px; font-size: 12px; text-align: center; }

    /* Location chips (filter + legend in one) */
    .location-filter-label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; }
    #location-chip-search { margin-bottom: 6px; }
    .loc-chips-scroll { max-height: 300px; overflow-y: auto; padding: 4px; border: 1px solid #e5e5e5; border-radius: 4px; background: #fafbfc; }
    .loc-chip {
        display: inline-flex; align-items: center; gap: 5px;
        max-width: 100%;
        margin: 3px 4px 3px 0; padding: 3px 9px;
        font-size: 11px; line-height: 1.4; color: #555;
        background: #fff; border: 1px solid #d8dde2; border-radius: 20px;
        cursor: pointer; transition: all 0.12s ease;
    }
    .loc-chip .loc-chip-label { max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .loc-chip:hover { border-color: #3498db; color: #333; }
    .loc-chip:focus { outline: 2px solid #3498db; outline-offset: 1px; }
    .loc-chip.active { background: #eaf4fd; border-color: #3498db; color: #2c81ba; font-weight: 600; }
    .loc-chip-all { border-style: dashed; color: #888; }
    .loc-chip-all.active { background: #f0f0f0; border-color: #999; color: #444; }
    .chips-disabled .loc-chip { opacity: 0.55; cursor: default; pointer-events: none; }
    .location-legend-item { display: inline-flex; align-items: center; margin: 2px 10px 2px 0; font-size: 11px; }
    .location-legend-swatch { width: 10px; height: 10px; border-radius: 50%; margin-right: 2px; display: inline-block; flex-shrink: 0; }
    .location-key-pinned { margin-top: 6px; font-size: 11px; }

    /* Grouped location chips (building > floor) */
    .loc-group { margin: 2px 0 4px; }
    .loc-group-header {
        display: flex; align-items: center; gap: 6px; width: 100%;
        padding: 5px 8px; margin: 0;
        font-size: 11px; font-weight: 700; color: #4a5560;
        background: #eef2f6; border: 1px solid #e0e6ec; border-radius: 4px;
        cursor: pointer; text-align: left;
        -webkit-transition: background 0.12s ease; transition: background 0.12s ease;
    }
    .loc-group-header:hover { background: #e6ecf2; }
    .loc-group-header:focus { outline: 2px solid #3498db; outline-offset: 1px; }
    .loc-group-chevron { width: 10px; font-size: 10px; color: #7a8794; -webkit-transition: transform 0.15s ease; transition: transform 0.15s ease; }
    .loc-group-header.expanded .loc-group-chevron,
    .loc-floor-header.expanded .loc-group-chevron { transform: rotate(90deg); }
    .loc-group-count { margin-left: auto; font-weight: 600; color: #97a2ad; }
    .loc-group-body { padding: 6px 0 2px 8px; }
    .loc-group-body[hidden], .loc-floor-body[hidden] { display: none !important; }
    .loc-floor { margin: 2px 0 4px; }
    .loc-floor-header {
        display: flex; align-items: center; gap: 6px; width: 100%;
        padding: 3px 6px; margin: 2px 0;
        font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #8a97a3;
        background: transparent; border: 0; cursor: pointer; text-align: left;
    }
    .loc-floor-header:hover { color: #5b6672; }
    .loc-floor-header:focus { outline: 2px solid #3498db; outline-offset: 1px; }
    .loc-floor-header .loc-group-chevron { width: 9px; font-size: 9px; }
    .loc-floor-header .loc-group-count { text-transform: none; letter-spacing: 0; }
    .loc-floor-body { padding: 2px 0 4px 10px; }

    /* Read-only calendar (view mode) */
    .calendar-readonly .fc-agenda-slots, .calendar-readonly .fc-day, .calendar-readonly .fc-event { cursor: default !important; }

    /* Calendar toolbar title */
    #wizard-calendar .fc-header-title { font-size: 14px !important; line-height: 20px !important; margin: 0 !important; }

    /* Accessibility */
    .wizard-steps li:focus { outline: 2px solid #3498db; outline-offset: 2px; }
    .wizard-steps li[role="button"] { cursor: pointer; }

    /* Repeat control */
    #add-session-modal .repeat-row { display: flex; align-items: center; gap: 6px; }
    #add-session-modal .repeat-row .form-control { width: 64px; height: auto; padding: 4px 8px; }

    /* Responsive */
    @media (max-width: 768px) {
        .step2-layout { flex-direction: column; height: auto; min-height: 0; }
        .step2-calendar { min-height: 380px; }
        .step2-sidebar { min-width: 0; }
        .step2-sessions-scroll { max-height: 320px; }
        .review-summary { flex-direction: column; align-items: stretch; gap: 6px; }
        .review-summary .review-budget { flex: none; }
    }
</style>
@endpush

@section('content')
<div class="panel panel-default schedule-page">
    <div class="panel-heading">
        <div class="panel-title" id="wizard-label">Programar Acción de Formación</div>
        <div class="panel-options">
            <a href="{{ url('u/af_programadas') }}" id="wizard-volver" class="btn btn-default btn-sm" title="Volver">Volver</a>
        </div>
    </div>
    <div class="panel-body">
        <div id="duration-zero-warning" class="alert alert-warning" style="display:none;" role="alert">Este curso no tiene una duración definida. No se pueden asignar sesiones.</div>
        <div id="no-locations-warning" class="alert alert-warning" style="display:none;" role="alert">No hay ubicaciones registradas. Registre al menos una ubicación para agendar sesiones.</div>

        @if($mode !== 'view')
        <ul class="wizard-steps" style="margin: 4px 20px 0;">
            <li class="active" data-step="1" role="button" tabindex="0" onclick="wizardStepClick(1)" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();wizardStepClick(1);}" title="Ir a Datos Generales">
                <span class="step-number">1</span>
                <span class="step-label">Datos Generales</span>
            </li>
            <li data-step="2" role="button" tabindex="0" onclick="wizardStepClick(2)" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();wizardStepClick(2);}" title="Ir a Sesiones">
                <span class="step-number">2</span>
                <span class="step-label">Sesiones</span>
            </li>
            <li data-step="3" role="button" tabindex="0" title="Revisar y Confirmar">
                <span class="step-number">3</span>
                <span class="step-label">Revisar y Confirmar</span>
            </li>
        </ul>
        @endif

        <div class="schedule-context text-muted" id="schedule-summary" style="display:none;">
            <span id="summary-course"></span>
            <span aria-hidden="true"> · </span>
            <span id="summary-facilitator"></span>
            <span id="summary-status-row" style="display:none;"> · <span id="summary-status" class="label"></span></span>
        </div>

        <form class="form-horizontal" method="POST" id="wizard-form" enctype="multipart/form-data">
            {!! csrf_field() !!}
            <input type="hidden" name="_method" value="POST">

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
                <div class="step2-layout">
                    <div class="step2-calendar">
                        <div class="calendar-env">
                            <div id="wizard-calendar"></div>
                        </div>
                    </div>
                    <div class="step2-sidebar">
                        <div class="step2-sidebar-header">
                            <div class="location-filter-block">
                                <label class="location-filter-label" for="location-chip-search">Ubicaciones <span class="text-muted">({{ $locations->count() }})</span></label>
                                @if($locations->count() > 10)
                                    <input type="search" id="location-chip-search" class="form-control input-sm" placeholder="Buscar ubicación…" autocomplete="off" aria-label="Buscar ubicación">
                                @endif
                                <div id="location-color-legend" class="loc-chips-scroll" role="group" aria-label="Filtrar calendario por ubicación"></div>
                                <div class="location-key-pinned">
                                    <span class="location-legend-item text-muted"><span class="location-legend-swatch" style="background:#999;"></span>Aula ocupada</span>
                                    <span class="location-legend-item text-muted"><span class="location-legend-swatch blocked-event-fac" style="cursor: default !important;"></span>Facilitador ocupado</span>
                                </div>
                            </div>
                            <select id="calendar-location-filter" class="hidden" tabindex="-1" aria-hidden="true">
                                <option value="">Todas las ubicaciones</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" data-building-id="{{ $location->floor?->building?->id }}" data-building-name="{{ $location->floor?->building?->name ?? 'Sin edificio' }}" data-floor-id="{{ $location->floor?->id }}" data-floor-name="{{ $location->floor?->name ?? 'Sin piso' }}" data-sessions="{{ (int) ($location->sessions_count ?? 0) }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                            <div class="sessions-header">
                                <h5>Sesiones (<span id="sessions-count-label">0</span>)<span id="sessions-budget" class="text-muted"></span></h5>
                                <button type="button" class="btn btn-primary btn-sm" id="add-session-btn" onclick="openNewSessionModal()">
                                    <i class="entypo-plus"></i> Agregar
                                </button>
                            </div>
                            <div class="progress mini-budget">
                                <div id="duration-bar" class="progress-bar" role="progressbar" aria-label="Horas asignadas" style="width: 0%"></div>
                            </div>
                        </div>
                        <div class="step2-sessions-scroll">
                            <table class="table table-striped table-bordered sessions-table">
                                <tbody id="wizard-sessions-tbody">
                                    <tr><td class="text-muted text-center">No hay sesiones agregadas.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 3: Review --}}
            <div class="wizard-step-panel" id="panel-step3">
                <div id="wizard-submit-alert"></div>
                <div class="review-summary">
                    <span class="review-summary-text"><strong id="review-sessions-count">0</strong> sesiones · <strong id="review-total-hours"></strong></span>
                    <div class="progress mini-budget review-budget">
                        <div id="review-duration-bar" class="progress-bar progress-bar-info" role="progressbar" aria-label="Horas asignadas" style="width: 0%"></div>
                    </div>
                </div>
                <table class="table table-striped table-bordered table-center review-table">
                    <thead>
                        <tr>
                            <th>Ubicación</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th class="text-center">Dur.</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="review-sessions-tbody"></tbody>
                </table>
            </div>

            @if($mode !== 'view')
            <div style="text-align: center; margin-top: 15px;">
                <button type="button" class="btn btn-default" id="wizard-btn-prev" onclick="wizardPrev(getCurrentStep())" style="display:none;">
                    <i class="fa fa-arrow-left"></i> Anterior
                </button>
                <button type="button" class="btn btn-primary" id="wizard-btn-next" onclick="wizardNext(getCurrentStep())">
                    Siguiente <i class="fa fa-arrow-right"></i>
                </button>
                <button type="button" class="btn btn-success" id="wizard-btn-submit" onclick="submitWizardSchedule()" style="display:none;">
                    <i class="fa fa-check"></i> Programar
                </button>
                <a href="{{ url('u/af_programadas') }}" class="btn btn-default" title="Cancelar">Cancelar</a>
                <div id="submit-gate-hint" style="display:none; margin-top: 10px;"></div>
            </div>
            @endif
        </form>
    </div>
</div>

{{-- Add Session Modal (triggered from calendar select or add button) --}}
<div class="modal fade" id="add-session-modal" tabindex="-1" role="dialog" aria-labelledby="add-session-label">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="add-session-label">Agregar Sesión</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="add-session-edit-id" value="">
                <form id="add-session-form">
                    <div class="session-messages">
                        <div id="add-session-alert"></div>
                        <div id="conflict-hint-group" style="display:none;">
                            <div id="conflict-hint" role="alert" aria-live="polite"></div>
                        </div>
                    </div>

                    <div class="session-section">
                        <span class="session-section-title">Ubicación</span>
                        <div class="row">
                            <div class="col-sm-12">
                                <label class="control-label" for="add-session-location">Aula / Espacio</label>
                                <select id="add-session-location" class="form-control" required>
                                    <option></option>
                                    @php
                                        $groupedLocations = $locations->sortBy('name')->groupBy(function ($location) {
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
                    </div>

                    <div class="session-section">
                        <span class="session-section-title">Fecha y hora</span>
                        <div class="row">
                            <div class="col-sm-4">
                                <label class="control-label" for="add-session-date">Fecha</label>
                                <input type="text" id="add-session-date" class="form-control dat2" required autocomplete="off" readonly>
                            </div>
                            <div class="col-sm-4">
                                <label class="control-label" for="add-session-start">Inicio</label>
                                <input type="text" id="add-session-start" class="form-control timepicker" required data-show-meridian="true" data-default-time="false" data-minute-step="15" aria-label="Hora de inicio">
                            </div>
                            <div class="col-sm-4">
                                <label class="control-label" for="add-session-end">Fin</label>
                                <input type="text" id="add-session-end" class="form-control timepicker" required data-show-meridian="true" data-default-time="false" data-minute-step="15" aria-label="Hora de fin">
                            </div>
                        </div>
                    </div>

                    <div class="session-section">
                        <span class="session-section-title">Duración</span>
                        <div class="duration-row">
                            <span class="duration-label">Horas</span>
                            <span class="duration-chips">
                                <button type="button" class="btn btn-default" data-hours="1" onclick="applyDurationChip(1)">1h</button>
                                <button type="button" class="btn btn-default" data-hours="2" onclick="applyDurationChip(2)">2h</button>
                                <button type="button" class="btn btn-default" data-hours="3" onclick="applyDurationChip(3)">3h</button>
                                <button type="button" class="btn btn-default" data-hours="4" onclick="applyDurationChip(4)">4h</button>
                            </span>
                            <span id="modal-remaining" aria-live="polite"></span>
                        </div>
                    </div>

                    <div class="session-section" id="repeat-group">
                        <span class="session-section-title">Repetición</span>
                        <div class="repeat-row">
                            <span>Repetir semanalmente</span>
                            <input type="number" id="repeat-count" class="form-control" value="1" min="1" max="20" aria-label="Número de repeticiones">
                            <span>veces</span>
                        </div>
                        <p class="repeat-help">Crea una sesión por semana durante el número de semanas indicado. Solo al agregar.</p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger pull-left hidden" id="add-session-delete-btn" onclick="confirmDeleteSession()">
                    <i class="entypo-trash"></i> Eliminar
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="add-session-confirm-btn" onclick="saveSessionFromModal()">Agregar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="delete-session-modal" tabindex="-1" role="dialog" aria-labelledby="delete-session-label">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="delete-session-label">Eliminar Sesión</h4>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar esta sesión? Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="delete-session-confirm">Eliminar</button>
            </div>
        </div>
    </div>
</div>

@if($mode !== 'view')
{{-- Leave warning modal (unsaved changes) --}}
<div class="modal fade" id="leave-warning-modal" tabindex="-1" role="dialog" aria-labelledby="leave-warning-label">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="leave-warning-label">Cambios sin guardar</h4>
            </div>
            <div class="modal-body">
                <p>Tiene cambios sin guardar en esta programación. Si sale ahora, perderá las sesiones y datos que ingresó.</p>
            </div>
            <div class="modal-footer" style="text-align: center;">
                <button type="button" class="btn btn-default" data-dismiss="modal">Quedarse</button>
                <button type="button" class="btn btn-danger" id="leave-warning-confirm">Salir sin guardar</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('JS')
<script src="{{url('assets/js/bootstrap-datepicker.js')}}"></script>
<script src="{{url('assets/js/bootstrap-timepicker.min.js')}}"></script>
<script>
var wizardSessions = [];
var courseDuration = @json($courseDuration);
var sessionCounter = 0;
var blockedSlotsCache = [];
var dirty = false;
var firstSessionDate = null;
var locationColorMap = {};
var locationGroupState = null;
var allowNavigation = false;
var maxStepReached = 1;
var pendingNavigation = null;
var sessionDeleteConfirmed = false;

var scheduleMode = '{{ $mode }}';
var scheduleId = @json($scheduledId);
var selectedCourseId = @json($selectedCourseId);
var selectedFacilitatorId = @json($selectedFacilitatorId);
var existingSessions = @json($existingSessions);
var statusName = '{{ $statusName ?? '' }}';
var statusBadge = '{{ $statusBadge ?? '' }}';

function showInlineAlert(containerId, type, message) {
    var icons = {
        danger: 'entypo-block',
        success: 'entypo-check',
        warning: 'entypo-warning',
        info: 'entypo-info-circled'
    };
    var calloutType = (type === 'error') ? 'danger' : type;
    $('#' + containerId).html(
        '<div class="session-callout session-callout--' + calloutType + '" role="alert" aria-live="polite">' +
        '<i class="' + (icons[calloutType] || 'entypo-info-circled') + ' callout-icon"></i>' +
        '<span class="callout-body">' + message + '</span>' +
        '</div>'
    );
}

function clearInlineAlert(containerId) {
    $('#' + containerId).empty();
}

function setFieldError(selector, message) {
    var field = $(selector);
    if (field.length === 0) return;
    var group = field.closest('.form-group, .col-sm-4, .col-sm-12, .col-lg-12');
    if (group.length === 0) group = field.parent();
    group.addClass('has-error');
    if (message) {
        var $help = group.find('.help-block.field-error').first();
        if ($help.length === 0) {
            $help = $('<span class="help-block field-error"></span>');
            field.after($help);
        }
        $help.text(message);
    }
}

function clearFieldErrors(scope) {
    $(scope).find('.has-error').removeClass('has-error');
    $(scope).find('.help-block.field-error').remove();
}

function getLocationColor(locationId) {
    if (!locationColorMap[locationId]) {
        var n = parseInt(locationId, 10);
        var hue = isNaN(n) ? (Object.keys(locationColorMap).length * 137.508) % 360 : (n * 137.508) % 360;
        locationColorMap[locationId] = 'hsl(' + Math.round(hue) + ', 65%, 45%)';
    }
    return locationColorMap[locationId];
}

function callout(type, icon, title, items) {
    var lis = items.map(function (i) {
        var prefix = i.date ? isoToDisplayDate(i.date) + ' · ' : '';
        return '<li>' + prefix + escapeHtml(cleanConflictTitle(i.title)) + ' · ' + formatTime12(i.start_time) + ' – ' + formatTime12(i.end_time) + '</li>';
    }).join('');
    return '<div class="session-callout session-callout--' + type + '">' +
        '<i class="' + icon + ' callout-icon"></i>' +
        '<span class="callout-body"><span class="callout-title">' + title + '</span><ul>' + lis + '</ul></span>' +
        '</div>';
}

function cleanConflictTitle(title) {
    return String(title).replace(/\s*\((?:Facilitador - )?Ocupado\)\s*$/i, '');
}

function pushUniqueConflict(list, entry) {
    var key = (entry.date || '') + '|' + entry.title + '|' + entry.start_time + '|' + entry.end_time;
    for (var i = 0; i < list.length; i++) {
        if ((list[i].date || '') + '|' + list[i].title + '|' + list[i].start_time + '|' + list[i].end_time === key) return;
    }
    list.push(entry);
}

function buildSessionDates(sessionDate, repeatCount) {
    var dates = [sessionDate];
    for (var r = 1; r < repeatCount; r++) {
        var d = new Date(sessionDate + 'T00:00:00');
        d.setDate(d.getDate() + 7 * r);
        var p = function (n) { return n < 10 ? '0' + n : '' + n; };
        dates.push(d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()));
    }
    return dates;
}

function collectConflictsForDates(dates, locationId, startTime, endTime, editId) {
    var merged = { location: [], facilitator: [] };
    dates.forEach(function (dt) {
        var c = findSessionConflicts(locationId, dt, startTime, endTime, editId);
        c.location.forEach(function (e) { e.date = dt; pushUniqueConflict(merged.location, e); });
        c.facilitator.forEach(function (e) { e.date = dt; pushUniqueConflict(merged.facilitator, e); });
    });
    return merged;
}

function renderConflictCallouts(conflicts) {
    var html = '';
    if (conflicts.location.length) {
        html += callout('warning', 'entypo-location', 'Aula ocupada', conflicts.location);
    }
    if (conflicts.facilitator.length) {
        html += callout('warning', 'entypo-user', 'Facilitador ocupado', conflicts.facilitator);
    }
    return html;
}

function findSessionConflicts(locationId, sessionDate, startTime, endTime, editId) {
    var sMin = timeToMinutes(startTime);
    var eMin = timeToMinutes(endTime);
    var result = { location: [], facilitator: [] };

    blockedSlotsCache.forEach(function (b) {
        if (b.session_date !== sessionDate) return;
        var bsMin = timeToMinutes(b.start_time);
        var beMin = timeToMinutes(b.end_time);
        if (!(sMin < beMin && eMin > bsMin)) return;

        var entry = { title: b.title, start_time: b.start_time, end_time: b.end_time };
        if (b.type === 'facilitator') {
            pushUniqueConflict(result.facilitator, entry);
        } else if (parseInt(b.location_id, 10) === parseInt(locationId, 10)) {
            pushUniqueConflict(result.location, entry);
        }
    });

    // Conflicts against other draft sessions in this same programación
    wizardSessions.forEach(function (s) {
        if (editId && s.id === parseInt(editId, 10)) return;
        if (s.session_date !== sessionDate) return;
        var ssMin = timeToMinutes(s.start_time);
        var seMin = timeToMinutes(s.end_time);
        if (!(sMin < seMin && eMin > ssMin)) return;

        if (parseInt(s.location_id, 10) === parseInt(locationId, 10)) {
            pushUniqueConflict(result.location, { title: s.location_name + ' (misma programación)', start_time: s.start_time, end_time: s.end_time });
        }
        pushUniqueConflict(result.facilitator, { title: 'Otra sesión de esta programación', start_time: s.start_time, end_time: s.end_time });
    });

    return result;
}

function hasSessionConflicts(conflicts) {
    return conflicts.location.length > 0 || conflicts.facilitator.length > 0;
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
    dirty = false;
    firstSessionDate = null;
    allowNavigation = false;
    maxStepReached = 1;
    pendingNavigation = null;
    $('#duration-info').text('');
    clearInlineAlert('wizard-alert');
    clearInlineAlert('wizard-submit-alert');
    $('#wizard-btn-prev').hide();
    $('#wizard-btn-next').show();
    $('#wizard-btn-submit').prop('disabled', true).hide();
    $('#calendar-location-filter').val('').trigger('change');
    locationGroupState = null;
    renderLocationChips();
    renderSessionTable();
    if (window.wizardCalendar) {
        window.wizardCalendar.fullCalendar('destroy');
        window.wizardCalendar = null;
    }
}

function wizardGoToStep(step) {
    if (step > maxStepReached) {
        maxStepReached = step;
    }
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
        setTimeout(function() {
            initWizardCalendar();
            if (firstSessionDate && window.wizardCalendar) {
                window.wizardCalendar.fullCalendar('gotoDate', firstSessionDate);
            }
        }, 100);
    } else if (step === 3) {
        renderReviewPanel();
    }

    updateDurationBar();
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
    var readOnly = (scheduleMode === 'view');
    $('.step2-calendar').toggleClass('calendar-readonly', readOnly);

    window.wizardCalendar = $('#wizard-calendar').fullCalendar({
        timezone: 'America/Caracas',
        firstDay: 1,
        header: { left: 'prev,next today', center: 'title', right: 'month,agendaWeek,agendaDay' },
        defaultView: 'agendaWeek',
        slotDuration: '00:30:00',
        allDaySlot: false,
        minTime: '06:00:00',
        maxTime: '22:00:00',
        contentHeight: calHeight,
        selectable: !readOnly,
        selectHelper: !readOnly,
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            agendaWeek: 'Semana',
            agendaDay: 'Día'
        },
        monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
        monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'],
        dayNames: ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'],
        dayNamesShort: ['Dom','Lun','Mar','Mié','Jue','Vie','Sá'],
        dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','Sá'],
        columnFormat: { week: 'ddd D/M' },
        allDayText: 'Todo el día',
        eventLimitText: function(n) {
            return '+' + n + ' más';
        },
        select: function(start, end) {
            window.wizardCalendar.fullCalendar('unselect');
            if (scheduleMode === 'view') return;
            var s = new Date(start.year(), start.month(), start.date(), start.hours(), start.minutes(), 0);
            var e = new Date(end.year(), end.month(), end.date(), end.hours(), end.minutes(), 0);
            // A plain click arrives as a single-slot selection: expand it to a 2h draft
            if ((e.getTime() - s.getTime()) <= 35 * 60 * 1000) {
                e = new Date(s.getTime() + 2 * 3600 * 1000);
                if (e.getDate() !== s.getDate() || e.getMonth() !== s.getMonth()) {
                    e = new Date(s.getFullYear(), s.getMonth(), s.getDate(), 23, 59, 0);
                }
            }
            openAddSessionModal(s, e);
        },
        eventClick: function(event) {
            if (scheduleMode === 'view') return;
            var id = String(event.id || '');
            if (event.rendering === 'background' || id.indexOf('session-') !== 0) return;
            openEditSessionModal(parseInt(id.substring(8), 10));
            return false;
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
            // Fetch a wide range so conflict detection works for any date the
            // user picks, not just the currently visible calendar week.
            var startStr, endStr;
            if (typeof moment === 'function') {
                startStr = moment().subtract(6, 'months').startOf('month').format('YYYY-MM-DD');
                endStr = moment().add(18, 'months').endOf('month').format('YYYY-MM-DD');
            } else {
                var ref = new Date();
                startStr = new Date(ref.getFullYear(), ref.getMonth() - 6, 1).toISOString().slice(0, 10);
                endStr = new Date(ref.getFullYear(), ref.getMonth() + 19, 0).toISOString().slice(0, 10);
            }
            var facilitatorId = $('#wizard-facilitador').val();
            $.ajax({
                url: '{{ url("u/af_programadas/blocked-slots") }}',
                type: 'GET',
                dataType: 'json',
                data: {
                    start: startStr,
                    end: endStr,
                    facilitator_id: facilitatorId || '',
                    exclude_scheduled_id: (scheduleMode === 'edit' && scheduleId) ? scheduleId : ''
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
                            var endStr2 = e.end ? e.end.split('T')[1] : startParts[1];
                            blockedSlotsCache.push({
                                location_id: e.location_id,
                                facilitator_id: e.facilitator_id || null,
                                type: e.type || 'location',
                                session_date: startParts[0],
                                start_time: startParts[1] ? startParts[1].slice(0, 5) : '00:00',
                                end_time: endStr2 ? endStr2.slice(0, 5) : '23:59',
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

function buildLocationGroups() {
    var buildings = {};
    $('#calendar-location-filter option[value!=""]').each(function () {
        var $o = $(this);
        var bId = String($o.data('building-id') || '');
        var fId = String($o.data('floor-id') || '');
        var bName = $o.data('building-name') || 'Sin edificio';
        var fName = $o.data('floor-name') || 'Sin piso';
        var sessions = parseInt($o.data('sessions'), 10) || 0;
        var bKey = 'b:' + (bId || 'none');
        var fKey = 'f:' + (fId || 'none');

        if (!buildings[bKey]) {
            buildings[bKey] = { key: bKey, name: bName, sessions: 0, floors: {} };
        }
        if (!buildings[bKey].floors[fKey]) {
            buildings[bKey].floors[fKey] = { key: fKey, name: fName, sessions: 0, locations: [] };
        }
        buildings[bKey].sessions += sessions;
        buildings[bKey].floors[fKey].sessions += sessions;
        buildings[bKey].floors[fKey].locations.push({ id: $o.val(), name: $o.text(), sessions: sessions });
    });

    var list = Object.keys(buildings).map(function (k) { return buildings[k]; });
    list.forEach(function (b) {
        var floors = Object.keys(b.floors).map(function (k) { return b.floors[k]; });
        floors.sort(function (x, y) { return (y.sessions - x.sessions) || x.name.localeCompare(y.name); });
        floors.forEach(function (f) {
            f.locations.sort(function (x, y) { return (y.sessions - x.sessions) || x.name.localeCompare(y.name); });
        });
        b.floorList = floors;
    });
    list.sort(function (x, y) { return (y.sessions - x.sessions) || x.name.localeCompare(y.name); });
    return list;
}

function defaultLocationGroupState(groups) {
    var state = {};
    groups.forEach(function (b) {
        state[b.key] = false;
        b.floorList.forEach(function (f) { state[f.key] = false; });
    });
    if (groups.length) {
        state[groups[0].key] = true;
        if (groups[0].floorList.length) {
            state[groups[0].floorList[0].key] = true;
        }
    }
    return state;
}

function locationChipHtml(location, current) {
    var active = String(location.id) === String(current);
    return '<button type="button" class="loc-chip' + (active ? ' active' : '') + '"' +
        ' data-location-id="' + escapeAttr(location.id) + '" aria-pressed="' + (active ? 'true' : 'false') + '"' +
        ' title="' + escapeAttr(location.name) + '">' +
        '<span class="location-legend-swatch" style="background:' + getLocationColor(location.id) + ';"></span>' +
        '<span class="loc-chip-label">' + escapeHtml(location.name) + '</span>' +
        '</button>';
}

function renderLocationChips() {
    var $legend = $('#location-color-legend');
    var current = $('#calendar-location-filter').val() || '';
    var $search = $('#location-chip-search');
    var query = ($search.length && $search.val()) ? $search.val().trim().toLowerCase() : '';
    var searching = query !== '';

    var groups = buildLocationGroups();
    if (!locationGroupState) {
        locationGroupState = defaultLocationGroupState(groups);
    }
    groups.forEach(function (b) {
        if (typeof locationGroupState[b.key] === 'undefined') locationGroupState[b.key] = false;
        b.floorList.forEach(function (f) {
            if (typeof locationGroupState[f.key] === 'undefined') locationGroupState[f.key] = false;
        });
    });

    // Auto-expand the chain that contains the active selection
    if (current) {
        groups.forEach(function (b) {
            b.floorList.forEach(function (f) {
                var hasActive = f.locations.some(function (l) { return String(l.id) === String(current); });
                if (hasActive) {
                    locationGroupState[b.key] = true;
                    locationGroupState[f.key] = true;
                }
            });
        });
    }

    var html = '<button type="button" class="loc-chip loc-chip-all' + (current === '' ? ' active' : '') + '"' +
        ' data-location-id="" aria-pressed="' + (current === '' ? 'true' : 'false') + '">Todas</button>';

    groups.forEach(function (b) {
        var floorsHtml = '';
        var buildingCount = 0;

        b.floorList.forEach(function (f) {
            var matching = f.locations.filter(function (l) {
                return !searching || l.name.toLowerCase().indexOf(query) !== -1;
            });
            if (!matching.length) return;

            buildingCount += matching.length;
            var floorExpanded = searching || !!locationGroupState[f.key];
            var chips = matching.map(function (l) { return locationChipHtml(l, current); }).join('');

            floorsHtml +=
                '<div class="loc-floor">' +
                    '<button type="button" class="loc-floor-header' + (floorExpanded ? ' expanded' : '') + '"' +
                        ' data-group-key="' + escapeAttr(f.key) + '" aria-expanded="' + (floorExpanded ? 'true' : 'false') + '">' +
                        '<i class="fa fa-chevron-right loc-group-chevron"></i>' +
                        '<span>' + escapeHtml(f.name) + '</span>' +
                        '<span class="loc-group-count">' + matching.length + '</span>' +
                    '</button>' +
                    '<div class="loc-floor-body"' + (floorExpanded ? '' : ' hidden') + '>' + chips + '</div>' +
                '</div>';
        });

        if (!buildingCount) return;

        var buildingExpanded = searching || !!locationGroupState[b.key];
        html +=
            '<div class="loc-group">' +
                '<button type="button" class="loc-group-header' + (buildingExpanded ? ' expanded' : '') + '"' +
                    ' data-group-key="' + escapeAttr(b.key) + '" aria-expanded="' + (buildingExpanded ? 'true' : 'false') + '">' +
                    '<i class="fa fa-chevron-right loc-group-chevron"></i>' +
                    '<span>' + escapeHtml(b.name) + '</span>' +
                    '<span class="loc-group-count">' + buildingCount + '</span>' +
                '</button>' +
                '<div class="loc-group-body"' + (buildingExpanded ? '' : ' hidden') + '>' + floorsHtml + '</div>' +
            '</div>';
    });

    $legend.html(html);
}

function openNewSessionModal() {
    if (scheduleMode === 'view') return;
    var now = new Date();
    now.setMinutes(Math.ceil(now.getMinutes() / 15) * 15, 0, 0);
    var end = new Date(now.getTime() + 2 * 3600 * 1000);
    if (end.getDate() !== now.getDate() || end.getMonth() !== now.getMonth()) {
        end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 0);
    }
    openAddSessionModal(now, end);
}

function openAddSessionModal(start, end, editId) {
    $('#add-session-form')[0].reset();
    $('#add-session-location').val('').trigger('change');
    $('#add-session-edit-id').val('');
    clearFieldErrors('#add-session-form');
    clearInlineAlert('add-session-alert');
    $('#conflict-hint-group').hide();

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
    var displayDate = sd + '-' + smo + '-' + sy;
    $('#add-session-start').val(to12h(sh + ':' + sm));
    $('#add-session-end').val(to12h(eh + ':' + em));
    $('#add-session-date').val(displayDate);

    if (editId) {
        $('#add-session-edit-id').val(editId);
        $('#add-session-modal .modal-title').text('Editar Sesión · ' + displayDate);
    } else {
        $('#add-session-modal .modal-title').text('Agregar Sesión · ' + displayDate);
    }
    $('#repeat-group').toggleClass('hidden', !!editId);
    $('#add-session-delete-btn').toggleClass('hidden', !editId);

    $('#add-session-modal').modal({ backdrop: false, keyboard: true });
    updateModalRemaining();
    updateConflictHint();
}

function openEditSessionModal(id) {
    if (scheduleMode === 'view') return;
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
    $('#add-session-location').val(session.location_id);
    updateModalRemaining();
    updateConflictHint();
}

function saveSessionFromModal() {
    clearFieldErrors('#add-session-form');
    clearInlineAlert('add-session-alert');

    var locationId = $('#add-session-location').val();
    var locationName = $('#add-session-location option:selected').text();
    var sessionDate = displayDateToIso($('#add-session-date').val());
    var startTime = to24h($('#add-session-start').val());
    var endTime = to24h($('#add-session-end').val());
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

    var repeatCount = (!editId) ? (parseInt($('#repeat-count').val(), 10) || 1) : 1;
    if (repeatCount < 1) repeatCount = 1;

    var totalHours = durHours * repeatCount;
    wizardSessions.forEach(function(s) {
        if (editId && s.id === parseInt(editId)) return;
        totalHours += s.duration_hours;
    });
    if (totalHours > courseDuration) {
        var remainingForAll = courseDuration - (totalHours - durHours * repeatCount);
        showInlineAlert('add-session-alert', 'danger',
            'Excede la duración total del curso (' + courseDuration + ' horas). ' +
            'Restante: ' + Math.max(0, remainingForAll) + ' horas.');
        return;
    }

    var candidateDates = buildSessionDates(sessionDate, repeatCount);

    var conflicts = collectConflictsForDates(candidateDates, locationId, startTime, endTime, editId);
    if (hasSessionConflicts(conflicts)) {
        $('#conflict-hint').html(renderConflictCallouts(conflicts));
        $('#conflict-hint-group').show();
        var message = candidateDates.length > 1
            ? 'Una o más repeticiones se superponen con otra sesión. El aula o el facilitador ya están ocupados en ese horario. Ajuste la fecha, la hora, el aula o el número de repeticiones.'
            : 'Este horario no está disponible. El aula o el facilitador ya tienen otra sesión en ese rango. Ajuste la fecha, la hora o el aula.';
        showInlineAlert('add-session-alert', 'danger', message);
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
        }
    } else {
        candidateDates.forEach(function (sessDate) {
            sessionCounter++;
            wizardSessions.push({
                id: sessionCounter,
                isNew: true,
                location_id: locationId,
                location_name: locationName,
                session_date: sessDate,
                start_time: startTime,
                end_time: endTime,
                duration_hours: durHours
            });
        });
    }

    $('#add-session-modal').modal('hide');
    $('#repeat-count').val(1);
    dirty = true;
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
    $('.duration-chips .btn').removeClass('active');
    if (dur > 0) {
        $('.duration-chips .btn[data-hours="' + dur + '"]').addClass('active');
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

function updateConflictHint() {
    var locationId = $('#add-session-location').val();
    var sessionDate = displayDateToIso($('#add-session-date').val());
    var startTime = to24h($('#add-session-start').val());
    var endTime = to24h($('#add-session-end').val());
    var editId = $('#add-session-edit-id').val();
    var repeatCount = (!editId) ? (parseInt($('#repeat-count').val(), 10) || 1) : 1;
    if (repeatCount < 1) repeatCount = 1;
    var $group = $('#conflict-hint-group');
    if (!locationId || !sessionDate || !startTime || !endTime || timeToMinutes(startTime) >= timeToMinutes(endTime)) {
        $group.hide();
        return;
    }
    var conflicts = collectConflictsForDates(buildSessionDates(sessionDate, repeatCount), locationId, startTime, endTime, editId);
    if (!hasSessionConflicts(conflicts)) {
        $group.hide();
        return;
    }
    $('#conflict-hint').html(renderConflictCallouts(conflicts));
    $group.show();
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

function isoToDisplayDate(iso) {
    if (!iso) return '';
    var parts = String(iso).split('-');
    if (parts.length !== 3) return iso;
    return parts[2] + '-' + parts[1] + '-' + parts[0];
}

function displayDateToIso(display) {
    if (!display) return '';
    var parts = String(display).split('-');
    if (parts.length !== 3) return display;
    return parts[2] + '-' + parts[1] + '-' + parts[0];
}

function removeWizardSession(id) {
    if (scheduleMode === 'view') return;
    wizardSessions = wizardSessions.filter(function(s) { return s.id !== id; });
    dirty = true;
    renderSessionTable();
    refreshCalendarEvents();
    if (getCurrentStep() === 3) {
        renderReviewPanel();
    }
}

function confirmDeleteSession() {
    if (scheduleMode === 'view') return;
    if (!$('#add-session-edit-id').val()) return;
    sessionDeleteConfirmed = false;
    $('#add-session-modal').modal('hide');
    $('#delete-session-modal').modal({ backdrop: false, keyboard: true });
}

function deleteSessionFromModal() {
    var editId = parseInt($('#add-session-edit-id').val(), 10);
    if (!editId) return;
    sessionDeleteConfirmed = true;
    removeWizardSession(editId);
    $('#delete-session-modal').modal('hide');
}

function formatTime12(t) {
    return to12h(t);
}

function escapeHtml(text) {
    return String(text == null ? '' : text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function escapeAttr(text) {
    return escapeHtml(text);
}

function renderSessionTable() {
    var $tbody = $('#wizard-sessions-tbody');
    $tbody.empty();
    $('#sessions-count-label').text(wizardSessions.length);
    if (wizardSessions.length === 0) {
        $tbody.append('<tr><td class="text-center"><i class="entypo-calendar" style="font-size: 26px; display: block; margin-bottom: 6px;"></i>' +
            '<span class="text-muted">No hay sesiones agregadas.<br>Haga clic en una fecha del calendario o use "Agregar".</span></td></tr>');
    } else {
        wizardSessions.forEach(function(s, i) {
            $tbody.append(
                '<tr>' +
                '<td>' +
                    '<div class="session-row-main">' +
                        '<span class="session-row-location" title="' + escapeAttr((i + 1) + '. ' + s.location_name) + '">' + (i + 1) + '. ' + escapeHtml(s.location_name) + '</span>' +
                        '<span class="session-row-actions">' +
                            '<button type="button" class="btn btn-info btn-xs" onclick="openEditSessionModal(' + s.id + ')" title="Editar sesión"><i class="entypo-pencil"></i></button> ' +
                            '<button type="button" class="btn btn-danger btn-xs" onclick="removeWizardSession(' + s.id + ')" title="Eliminar sesión"><i class="entypo-trash"></i></button>' +
                        '</span>' +
                    '</div>' +
                    '<div class="session-row-meta">' + isoToDisplayDate(s.session_date) + ' · ' + formatTime12(s.start_time) + ' – ' + formatTime12(s.end_time) + ' · ' + s.duration_hours + ' hrs</div>' +
                '</td>' +
                '</tr>'
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
    var $budget = $('#sessions-budget');
    if (courseDuration > 0) {
        var budgetText = ' · ' + assigned + 'h de ' + courseDuration + 'h';
        if (remaining < 0) {
            budgetText += ' (excedido)';
        }
        $budget.text(budgetText).attr('aria-live', 'polite');
    } else {
        $budget.text('').removeAttr('aria-live');
    }

    var $submitBtn = $('#wizard-btn-submit');
    var $gateHint = $('#submit-gate-hint');
    if (scheduleMode === 'view') {
        $submitBtn.hide();
        $gateHint.hide();
    } else if (scheduleMode === 'edit') {
        $submitBtn.prop('disabled', wizardSessions.length === 0);
        $gateHint.hide();
    } else {
        if (assigned === courseDuration && wizardSessions.length > 0) {
            $submitBtn.prop('disabled', false);
            $submitBtn.removeAttr('title');
            $gateHint.hide();
        } else {
            $submitBtn.prop('disabled', true);
            var gateMsg;
            if (courseDuration === 0) {
                gateMsg = 'Seleccione una acción de formación con duración definida para habilitar "Programar".';
            } else if (remaining > 0) {
                gateMsg = 'Faltan ' + remaining + ' hora(s) por asignar para habilitar "Programar".';
            } else {
                gateMsg = 'Ha excedido la duración del curso por ' + Math.abs(remaining) + ' hora(s). Ajuste las sesiones para habilitar "Programar".';
            }
            $submitBtn.attr('title', gateMsg);
            if (getCurrentStep() === 3) {
                $gateHint.html('<div class="session-callout session-callout--info" role="status"><i class="entypo-info-circled callout-icon"></i><span class="callout-body">' + gateMsg + '</span></div>').show();
            } else {
                $gateHint.hide();
            }
        }
    }

    if ($('#wizard-titulo').val() && courseDuration === 0) {
        $('#duration-zero-warning').show();
    } else {
        $('#duration-zero-warning').hide();
    }
}

function renderReviewPanel() {
    var $tbody = $('#review-sessions-tbody');
    $tbody.empty();
    var totalHours = 0;
    if (wizardSessions.length === 0) {
        $tbody.append('<tr><td colspan="5" class="text-center text-muted"><i class="entypo-calendar" style="font-size: 22px; display: block; margin-bottom: 6px;"></i>No hay sesiones para revisar.</td></tr>');
    }
    wizardSessions.forEach(function(s) {
        totalHours += s.duration_hours;
        $tbody.append(
            '<tr>' +
            '<td title="' + escapeAttr(s.location_name) + '">' + escapeHtml(s.location_name) + '</td>' +
            '<td>' + isoToDisplayDate(s.session_date) + '</td>' +
            '<td>' + formatTime12(s.start_time) + ' – ' + formatTime12(s.end_time) + '</td>' +
            '<td class="text-center">' + s.duration_hours + ' hrs</td>' +
                '<td class="actions" style="white-space: nowrap;">' +
                '<button type="button" class="btn btn-info btn-xs" onclick="openEditSessionModal(' + s.id + ')" title="Editar sesión"><i class="entypo-pencil"></i></button> ' +
                '<button type="button" class="btn btn-danger btn-xs" onclick="removeReviewSession(' + s.id + ')" title="Eliminar sesión"><i class="entypo-trash"></i></button>' +
            '</td>' +
            '</tr>'
        );
    });
    $('#review-total-hours').text(totalHours + ' / ' + courseDuration + ' horas');
    $('#review-sessions-count').text(wizardSessions.length);

    updateDurationBar();

    var pct = courseDuration > 0 ? Math.min((totalHours / courseDuration) * 100, 100) : 0;
    var barClass = pct >= 100 ? 'progress-bar-success' : 'progress-bar-info';
    $('#review-duration-bar').css('width', pct + '%').attr('class', 'progress-bar ' + barClass);
}

function removeReviewSession(id) {
    if (scheduleMode === 'view') return;
    wizardSessions = wizardSessions.filter(function(s) { return s.id !== id; });
    dirty = true;
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
                id: s.isNew ? null : s.id,
                location_id: s.location_id,
                session_date: s.session_date,
                start_time: s.start_time,
                end_time: s.end_time
            };
        })
    };

    var url;
    var method;
    if (scheduleMode === 'edit') {
        url = '{{ url('u/af_programadas') }}/' + scheduleId;
        method = 'PUT';
        payload.scheduled_id = scheduleId;
    } else {
        url = '{{ url('u/af_programadas/schedule') }}';
        method = 'POST';
    }

    var $submitBtn = $('#wizard-btn-submit');
    $submitBtn.prop('disabled', true);
    $submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

    $.ajax({
        url: url,
        type: method,
        data: JSON.stringify(payload),
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            dirty = false;
            allowNavigation = true;
            window.location.href = '{{ url('u/af_programadas') }}';
        },
        error: function(xhr) {
            allowNavigation = false;
            $submitBtn.prop('disabled', false);
            $submitBtn.html(scheduleMode === 'edit' ? '<i class="fa fa-check"></i> Actualizar' : '<i class="fa fa-check"></i> Programar');
            var msg = 'Error al guardar el curso.';
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

function getCurrentStep() {
    if ($('#panel-step1').hasClass('active')) return 1;
    if ($('#panel-step2').hasClass('active')) return 2;
    if ($('#panel-step3').hasClass('active')) return 3;
    return 1;
}

function updateSummary() {
    var courseText = $('#wizard-titulo option:selected').text();
    var facText = $('#wizard-facilitador option:selected').text();
    $('#summary-course').text(courseText ? courseText : '—');
    $('#summary-facilitator').text(facText ? facText : '—');
    if (statusName) {
        $('#summary-status').text(statusName).attr('class', 'label label-' + (statusBadge || 'default'));
        $('#summary-status-row').show();
    }
    $('#schedule-summary').show();
}

function initSchedulePage() {
    $('#wizard-titulo').select2({ allowClear: true, placeholder: 'Seleccionar acción de formación' });
    $('#wizard-facilitador').select2({ allowClear: true, placeholder: 'Seleccionar facilitador' });

    $('#wizard-titulo').on('change', function() {
        var selected = $(this).find('option:selected');
        courseDuration = parseInt(selected.data('duration')) || 0;
        $('#duration-info').text('Duración del curso: ' + courseDuration + ' horas');
        updateDurationBar();
        updateSummary();
    });
    $('#wizard-facilitador').on('change', function() {
        refreshCalendarEvents();
        updateSummary();
    });
    $('#calendar-location-filter').on('change', function() {
        refreshCalendarEvents();
        renderLocationChips();
    });

    $('#location-color-legend').on('click', '.loc-chip', function() {
        var id = String($(this).data('location-id') || '');
        var current = $('#calendar-location-filter').val() || '';
        var next = (current === id) ? '' : id;
        $('#calendar-location-filter').val(next).trigger('change');
    });

    $('#location-color-legend').on('click', '.loc-group-header, .loc-floor-header', function() {
        var key = String($(this).data('group-key') || '');
        if (!key || !locationGroupState) return;
        var expanded = !locationGroupState[key];
        locationGroupState[key] = expanded;
        $(this).toggleClass('expanded', expanded).attr('aria-expanded', expanded ? 'true' : 'false');
        $(this).next().prop('hidden', !expanded);
    });

    $('#location-chip-search').on('input', function() {
        renderLocationChips();
    });

    if (scheduleMode === 'edit' || scheduleMode === 'view') {
        if (selectedCourseId) {
            $('#wizard-titulo').val(selectedCourseId).trigger('change');
        }
        if (selectedFacilitatorId) {
            $('#wizard-facilitador').val(selectedFacilitatorId).trigger('change');
        }
        wizardSessions = (existingSessions || []).map(function(s) {
            sessionCounter = Math.max(sessionCounter, parseInt(s.id) || 0);
            return {
                id: s.id,
                isNew: false,
                location_id: s.location_id,
                location_name: s.location_name,
                session_date: s.session_date,
                start_time: s.start_time,
                end_time: s.end_time,
                duration_hours: s.duration_hours
            };
        });
        var dates = wizardSessions.map(function(s) { return s.session_date; }).sort();
        firstSessionDate = dates.length ? dates[0] : null;
        renderSessionTable();
        updateDurationBar();
        updateSummary();
    } else {
        resetWizard();
    }

    renderLocationChips();

    if (scheduleMode === 'edit') {
        $('#wizard-label').text('Editar Acción de Formación Programada');
        $('#wizard-btn-submit').html('<i class="fa fa-check"></i> Actualizar');
    } else if (scheduleMode === 'view') {
        $('#wizard-label').text('Detalles de la Acción de Formación Programada');
        $('#wizard-titulo, #wizard-facilitador, #calendar-location-filter').prop('disabled', true);
        $('#wizard-btn-submit').hide();
        $('#add-session-btn, .sessions-table .session-row-actions, .review-table .actions').hide();
        wizardGoToStep(2);
    }

    if ($('#calendar-location-filter option').length <= 1) {
        $('#no-locations-warning').show();
        $('#add-session-btn').prop('disabled', true);
    }

    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedWork()) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
}

function hasUnsavedWork() {
    if (scheduleMode === 'view' || allowNavigation) return false;
    if (dirty) return true;
    if (maxStepReached > 1) return true;
    return scheduleMode === 'create' && !!($('#wizard-titulo').val() || $('#wizard-facilitador').val());
}

function isNavigatingLink(link) {
    if (link.hasAttribute('data-dismiss') || link.hasAttribute('data-toggle')) return false;
    if (link.getAttribute('target') === '_blank' || link.hasAttribute('download')) return false;
    var href = link.getAttribute('href');
    if (!href || href === '#' || href.indexOf('#') === 0) return false;
    if (/^(javascript:|mailto:|tel:)/i.test(href)) return false;
    return true;
}

function openLeaveWarning(link) {
    if ($('#leave-warning-modal').hasClass('in')) return;
    var href = link.getAttribute('href');
    if (href.indexOf('/logout') !== -1) {
        pendingNavigation = { type: 'logout' };
    } else {
        pendingNavigation = { type: 'url', url: link.href || href };
    }
    $('#leave-warning-modal').modal('show');
}

document.addEventListener('click', function(e) {
    if (!hasUnsavedWork()) return;
    var link = e.target.closest ? e.target.closest('a[href]') : null;
    if (!link || !isNavigatingLink(link)) return;
    e.preventDefault();
    e.stopPropagation();
    openLeaveWarning(link);
}, true);

$(function() {
    $('#leave-warning-confirm').on('click', function() {
        allowNavigation = true;
        $('#leave-warning-modal').modal('hide');
        if (pendingNavigation && pendingNavigation.type === 'logout') {
            var logoutForm = document.getElementById('logout-form');
            if (logoutForm) logoutForm.submit();
        } else if (pendingNavigation && pendingNavigation.url) {
            window.location.href = pendingNavigation.url;
        }
    });

    $('#delete-session-confirm').on('click', function() {
        deleteSessionFromModal();
    });

    $('#delete-session-modal').on('hidden.bs.modal', function() {
        if (!sessionDeleteConfirmed) {
            $('#add-session-modal').modal({ backdrop: false, keyboard: true });
        }
    });
});

$(function() {
    initSchedulePage();
});

$('#add-session-modal').on('shown.bs.modal', function() {
    $('#add-session-location').select2({ allowClear: true, placeholder: 'Seleccionar ubicación', dropdownParent: $('#add-session-modal') });
    $('.dat2', $(this)).datepicker({ format: "dd-mm-yyyy", todayHighlight: true });
    $('.timepicker', $(this)).timepicker({ showMeridian: true, defaultTime: false, minuteStep: 15 });
    var sel = $('#add-session-location').data('select2');
    if (sel && sel.focusser) {
        var focusser = sel.focusser;
        setTimeout(function() { if (focusser) { focusser.focus(); } }, 50);
    }
});

$('#add-session-modal').on('hidden.bs.modal', function() {
    $('#add-session-location').select2('destroy');
    if ($('.modal.in').length === 0) {
        $('.modal-backdrop').not('.in').remove();
    }
});

$('#add-session-start, #add-session-end, #add-session-date').on('keydown change', function() {
    updateModalRemaining();
    updateConflictHint();
});

$('#add-session-location').on('change', function() {
    updateConflictHint();
});

$('#repeat-count').on('input change', function() {
    updateConflictHint();
});

$('#add-session-form').on('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && !$(e.target).hasClass('select2-focusser')) {
        e.preventDefault();
        saveSessionFromModal();
    }
});
</script>
@endpush
