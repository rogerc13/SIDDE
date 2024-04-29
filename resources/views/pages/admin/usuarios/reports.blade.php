@push('JS')
    <script src="{{asset('assets/js/Moment.js')}}"></script>    
    <script src="{{asset('assets/js/chartjs/Chart.js')}}"></script>
    <script src="{{asset('assets/js/reports.js')}}"></script>
@endpush
@extends('layouts.admin')
@section('content')
<img class="hidden-lg hidden-md hidden-sm hidden-xs visible-print" src="{{asset('assets/images/PDV_S.A._logo.svg')}}" alt="">
    <h1>S.I.D.D.E. Reportes</h1>
    <div class="container-fluid">
        <div class="row hidden-print form-group">
        <div class="col-xs12 col-md-12">
            <form method="POST" action="" id="" class="form-horizontal report-form">
            {!! csrf_field() !!}
            <div class="row">
                <div class="col-md-12">
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="report-type" class='control-label'>Tipo de Reporte</label>
                        <select name="report-type" id="report-type" class="form-control selector" >
                            <option value="date" selected>Cantidad de Acciones de Formación</option>
                            <option value="category">Cantidad de Acciones de Formación Por Areas de Conocimiento</option>
                            <option value="status">Cantidad de Acciones de Formación Por Estatus</option>
                            <option value="most-scheduled">Acciónes de Formación más Programadas</option>
                            <option value="not-scheduled">Acciones de Formación No Programadas</option>
                            <option value="participant-by-quantity">Acciones de Formación por Cantidad de Participantes</option>
                            <option value="duration">Duración de Acciones de Formación</option>
                            <option value="participant-by-status">Estatus de Participantes</option>
                            {{-- <option value="participant-average">Promedio de Participantes</option> --}}
                            
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-6">   
                        <label for="date_range" class='control-label'>Rango de Tiempo</label>
                        <select name="date_range" id="date_range" class="form-control">
                            <option value="1" selected>1 Mes</option>
                            <option value="4">4 Meses</option>
                            <option value="6">6 Meses</option>                        
                            <option value="12">1 año</option>
                            <option value="all">Todo el Tiempo</option>
                        </select>   
                    </div>
                <!-- </div>
                <div class="form-group"> -->
                    <div class="col-md-6">
                        <label for="step" class='control-label'>Intervalo de Tiempo</label>
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
                </div>
                <div class="form-group">
                    <div class="col-md-6 participant-status-container">
                        <label for="participant_status" class='control-label'>Estatus de Participantes</label>
                        <select name="participant_status" id="participant_status" class="form-control">
                            
                        </select>
                    </div>
                </div>
            </div>
            </div> {{-- row --}}
            <button type="submit" class="generate btn btn-primary"><span>Generar Reporte</span></button> 
            <button type="" class="btn btn-secondary print-report" disabled><span>Imprimir Reporte</span></button> 
        </form>
        </div>{{-- form --}}
    </div>{{-- row hide --}}
    <div class="row">
        <div class="course-amount-number">
            <span></span>
        </div>
        <div class="course-pie-graph"></div>
    </div>
    <div class="row row-graphs">
        
        {{--  --}}
    </div>
    <div class="row">
        <div class="col-xs12 col-md-12 table-col-helper">
            <table class="course-list table table-striped table-bordered table-center">
                <thead>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    </div>{{-- end class container --}}

@endsection