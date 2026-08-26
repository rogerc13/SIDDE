@push('JS')
    <script src="{{asset('assets/js/Moment.js')}}"></script>
    <script src="{{ asset('assets/js/chartjs/chart.umd.js') }}"></script>
    <script src="{{ asset('assets/js/chartjs/chartjs-adapter-moment.min.js') }}"></script>
     <script src="{{asset('assets/js/reports.js')}}"></script>
     <link rel="stylesheet" href="{{ asset('assets/css/reports-responsive.css') }}">
@endpush
@extends('layouts.admin')
@section('content')
<img class="hidden-lg hidden-md hidden-sm hidden-xs visible-print" src="{{asset('assets/images/PDV_S.A._logo.svg')}}" alt="">
    <h1>S.I.D.D.E. Reportes</h1>
    <div class="container-fluid">
        <div class="row hidden-print form-group">
        <div class="col-xs-12 col-md-12">
            <form method="POST" action="" id="" class="form-horizontal report-form">
            {!! csrf_field() !!}
            <div class="row">
                <div class="col-md-12">

                {{-- Row 1: Report type + Status filter + Step + Actions --}}
                <div class="form-group report-filters-row">
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <label for="report-type" class="control-label">Tipo de Reporte</label>
                        <select name="report-type" id="report-type" class="form-control selector">
                            <option value="date" selected>Cantidad de Acciones de Formación</option>
                            <option value="category">Cantidad de Acciones de Formación Por Areas de Conocimiento</option>
                            <option value="status">Cantidad de Acciones de Formación Por Estatus</option>
                            <option value="most-scheduled">Acciónes de Formación más Programadas</option>
                            <option value="not-scheduled">Acciones de Formación No Programadas</option>
                            <option value="canceled">Acciones de Formación Canceladas</option>
                            <option value="participant-by-quantity">Acciones de Formación por Cantidad de Participantes</option>
                            <option value="duration">Duración de Acciones de Formación</option>
                            <option value="participant-by-status">Estatus de Participantes</option>
                            <option value="participant-average">Promedio de Participantes</option>
                            <option value="gender">Distribución de Participantes por Género</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6 col-xs-12 participant-status-container" style="display:none;">
                        <label for="participant_status" class="control-label">Filtrar por Estatus</label>
                        <select name="status_id" id="participant_status" class="form-control">
                            <option value="">Todos los estatus</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-12" id="date-step-col">
                        <label for="step" class="control-label">Intervalo</label>
                        <select name="step" id="step" class="form-control">
                            <option value="1 day" selected>Diario</option>
                            <option value="1 week">Semanal</option>
                            <option value="15 days">Quincenal</option>
                            <option value="1 month">Mensual</option>
                            <option value="4 months">Cada 4 meses</option>
                            <option value="6 months">Cada 6 meses</option>
                            <option value="1 year">Anual</option>
                        </select>
                    </div>
                    <div class="col-md-4 col-sm-8 col-xs-12 report-actions">
                        <label class="control-label">&nbsp;</label>
                        <div class="report-actions-buttons">
                            <button type="submit" class="generate btn btn-primary"><i class="fa fa-bar-chart"></i> Generar</button>
                            <button type="button" class="btn btn-default print-report" disabled><i class="fa fa-print"></i> Imprimir</button>
                        </div>
                    </div>
                </div>

                {{-- Row 2: Date range + Presets --}}
                <div class="form-group report-date-row date-preset-group">
                    <div class="col-md-2 col-sm-4 col-xs-12">
                        <label for="start_date" class="control-label">Desde</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" />
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-12">
                        <label for="end_date" class="control-label">Hasta</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" />
                    </div>
                    <div class="col-md-8 col-sm-12 col-xs-12">
                        <label class="control-label">Rango Rápido</label>
                        <div class="date-preset-buttons">
                            <button type="button" class="btn btn-default btn-sm date-preset-btn" data-preset="7d">Últimos 7 días</button>
                            <button type="button" class="btn btn-default btn-sm date-preset-btn active" data-preset="30d">Últimos 30 días</button>
                            <button type="button" class="btn btn-default btn-sm date-preset-btn" data-preset="month">Este Mes</button>
                            <button type="button" class="btn btn-default btn-sm date-preset-btn" data-preset="quarter">Trimestre Actual</button>
                            <button type="button" class="btn btn-default btn-sm date-preset-btn" data-preset="year">Año Actual</button>
                            <button type="button" class="btn btn-default btn-sm date-preset-btn" data-preset="custom">Personalizado</button>
                        </div>
                    </div>
                </div>

                </div>
            </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="course-amount-number">
            <span></span>
        </div>
        <div class="course-pie-graph"></div>
    </div>
    <div class="loader text-center">
        <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
        <span class="sr-only">Cargando...</span>
    </div>
    <div class="row row-graphs">

    </div>
    <div class="row">
        <div class="col-xs-12 col-md-12 table-col-helper">
            <table class="course-list table table-striped table-bordered table-center">
                <thead>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    </div>

@endsection
