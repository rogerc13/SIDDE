@push('JS')
    <script src="{{asset('assets/js/Moment.js')}}"></script>    
    <script src="{{asset('assets/js/chartjs/Chart.js')}}"></script>
    <script src="{{asset('assets/js/reports.js')}}"></script>
@endpush
@extends('layouts.admin')
@section('content')
    <h1>Reportes</h1>
    <div class="container-fluid">
        <div class="row hidden-print form-group">
        <div class="col-xs12 col-md-12">
            <form method="POST" action="" id="" class="form-horizontal report-form">
            {!! csrf_field() !!}
            <div class="row">
                <div class="col-md-12">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="report-type">Tipo de Reporte</label>
                        <select name="report-type" id="report-type" class="form-control selector" >
                            <option value="date" selected>Cantidad de Cursos</option>
                            <option value="category">Cantidad de Cursos Por Categoria</option>
                            <option value="status">Cantidad de Cursos Por Estatus</option>
                            <option value="duration">Duracion de Cursos</option>
                            <option value="participant-by-quantity">Cursos por Cantidad de Participantes</option>
                            <option value="participant-by-status">Estatus de Participantes</option>
                            <option value="participant-average">Promedio de Participantes</option>
                            <option value="most-scheduled">Cursos más Programados</option>
                            <option value="not-scheduled">Cursos No Programados</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                <div class="form-group">   
                    <label for="date_range">Rango de Tiempo</label>
                    <select name="date_range" id="date_range" class="form-control">
                        <option value="1" selected>1 Mes</option>
                        <option value="4">4 Meses</option>
                        <option value="6">6 Meses</option>                        
                        <option value="12">1 año</option>
                        <option value="all">Todo el Tiempo</option>
                    </select>   
                </div>
                </div>
                <div class="col-md-6">
                <div class="form-group">
                    <label for="step">Intervalo de Tiempo</label>
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
                <div class="col-md-6 participant-status-container">
                    <div class="form-group">
                        <label for="participant_status">Estatus de Participantes</label>
                        <select name="participant_status" id="participant_status" class="form-control">
                            <option value="1">En Curso</option>
                            <option value="2">Susperndido / Reprobado</option>
                            <option value="3">Aprobado</option>
                            <option value="4">Cancelado</option>
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
    <div class="row">
        <div class="col-xs-12 col-md-6 graph-container">
            <canvas id="myChart" width="400" height="400"></canvas>
        </div>
        <div class="col-xs-12 col-md-6 doughnut-container">
            <canvas id="doughnut" width="400" height="400"></canvas>
        </div>
    </div>
    <div class="row">
        <div class="col-xs12 col-md-12">
            <table class="course-list table table-striped table-bordered table-center">
                <thead>
                        <td>Titulo</td>
                        <td>Fecha de Inicio</td>
                        <td>Fecha Fin</td>
                        <td>Estado del Curso</td>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
    </div>{{-- end class container --}}

@endsection