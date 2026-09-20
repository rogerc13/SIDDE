@push('CSS')
    <link rel="stylesheet" href="{{ asset('assets/css/reports-responsive.css') }}">
@endpush
@push('JS')
    <script src="{{asset('assets/js/Moment.js')}}"></script>
    <script src="{{ asset('assets/js/chartjs/chart.umd.js') }}"></script>
    <script src="{{ asset('assets/js/chartjs/chartjs-adapter-moment.min.js') }}"></script>
     <script type="module" src="{{ asset('assets/js/reports/main.js') }}"></script>
@endpush
@extends('layouts.admin')
@section('content')
<img class="hidden-lg hidden-md hidden-sm hidden-xs visible-print" src="{{asset('assets/images/PDV_S.A._logo.svg')}}" alt="">
<div class="container-fluid reports-page">
    <div class="report-page-header">
        <div>
            <div class="report-eyebrow">Reportes</div>
            <h1 class="report-title">S.I.D.D.E. Reportes</h1>
            <p class="report-subtitle" id="report-range-label">Seleccione los filtros y genere un reporte.</p>
        </div>
    </div>

    <div class="report-toolbar hidden-print">
        <form method="POST" action="" class="form-horizontal report-form">
            {!! csrf_field() !!}

            {{-- Row 1: Report type + Status filter + Step + Actions --}}
            <div class="form-group report-filters-row">
                <div class="col-md-3 col-sm-6 col-xs-12">
                    <label for="report-type" class="control-label">Tipo de Reporte</label>
                    <select name="report-type" id="report-type" class="form-control selector">
                        @foreach(($reportTypes ?? []) as $reportType)
                            <option value="{{ $reportType['value'] }}" @if($loop->first) selected @endif>{{ $reportType['label'] }}</option>
                        @endforeach
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
                        <button type="submit" class="generate btn btn-primary"><i class="fa fa-bar-chart" aria-hidden="true"></i> Generar</button>
                        <button type="button" class="btn btn-default print-report" disabled><i class="fa fa-print" aria-hidden="true"></i> Imprimir</button>
                        <button type="button" class="btn btn-default export-report" data-format="csv"><i class="fa fa-file-text-o" aria-hidden="true"></i> CSV</button>
                        <button type="button" class="btn btn-default export-report" data-format="xlsx"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Excel</button>
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
        </form>
    </div>

    <div class="row">
        <div class="course-amount-number">
            <span></span>
        </div>
        <div class="course-pie-graph"></div>
    </div>

    <div class="report-summary row hidden" aria-live="polite"></div>
    <div class="report-insight hidden" aria-live="polite"></div>

    <div class="loader text-center" role="status" aria-live="polite">
        <i class="fa fa-spinner fa-spin fa-3x fa-fw" aria-hidden="true"></i>
        <span class="sr-only">Cargando...</span>
    </div>

    <div class="row row-graphs" aria-live="polite"></div>

    <div class="row">
        <div class="col-xs-12 col-md-12 table-col-helper"></div>
    </div>
</div>

@endsection
