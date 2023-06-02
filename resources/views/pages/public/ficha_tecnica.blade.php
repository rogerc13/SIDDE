@extends('layouts.admin')

@section('content')
<div class="ficha-tecnica">
        
        <table class="table ficha-table">
            <thead>
                <tr scope="row" colspan="4">
                    <img src="{{asset('assets/images/PDV_S.A._logo.svg')}}" alt="">
                </tr>
            </thead>
            <hr class = "ficha-red-line">
            <tbody>
                <tr>
                    <th scope="row"><p class="ficha-course-code">Código:<p></th>
                    <td colspan="4"><p class="ficha-course-code">{{$curso->codigo}}</p></td>
                </tr>
                <tr>
                    <th scope="row" colspan="5"><p class="ficha-course-title">{{$curso->titulo}}</p></th>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Área de conocimiento</p></td>
                    <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-categoria-name">{{$curso->categoria->nombre}}</p></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Sub-área de conocimiento</p></td>
                    <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-sub-area ficha-course-categoria-name">{{$curso->categoria->nombre}}</p></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Modalidad</p></td>
                    <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-modalidad ficha-course-categoria-name">{{$curso->modalidad}}</p></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Objetivo</p></td>
                    <td colspan="4" rowspan="1">{{$curso->objetivo}}</td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Contenido</p></td>
                    <td colspan="4" rowspan="1">{{$curso->contenido}}</td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Duración</p></td>
                    <td colspan="4" rowspan="1"><p>{{$curso->duracion}} @if($curso->duracion > 1)Horas @else Hora @endif
                    </p></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Dirigido a</p></td>
                    <td colspan="4" rowspan="1"><p>{{$curso->dirigido}}</p></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Acciones de formación previa</p></td>
                    <td colspan="4" rowspan="1"><p>Ninguna</p></td>
                </tr>
                <tr>
                    <th scope="row"><p>Número de participantes</p></th>
                    <td><p class="ficha-course-capacity">Mínimo</p></td>
                    <td><p>{{$curso->min}}</p></td>
                    <td><p class="ficha-course-capacity">Máximo</p></td>
                    <td><p>{{$curso->max}}</p></td>
                </tr>
                
            </tbody>
        </table>
    </div>

@stop

@section('modals')

@stop
