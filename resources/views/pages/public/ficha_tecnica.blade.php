

@extends('layouts.admin')



@section('content')
{{--needs style--}}
<div class="row buttons hide-print">
    <div class="col-md-8">
        <a class="btn btn-blue" href="{{url("u/mis_acciones")}}" role="button"><i class="entypo-back" aria-hidden="true"></i>Regresar</a>
    </div>
    <div class="col-md-2">
        <a class="btn btn-default" href="" role="button"><i class="entypo-download"></i>Descargar Documentos</a>
    </div>
    <div class="col-md-2">
        <a class="btn btn-success save-ficha" href="" role="button"><i class="entypo-doc"></i>Descargar Ficha</a>
    </div>
</div>
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
                <th scope="row" class="no-border-top"><p class="ficha-course-code">Código:<p></th>
                <td colspan="4" class="no-border-top"><p class="ficha-course-code">{{$curso->codigo}}</p></td>
            </tr>
            <tr>
                <th scope="row" colspan="5" class="no-border-top"><p class="ficha-course-title">{{$curso->titulo}}</p></th>
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
                <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-modalidad ficha-course-categoria-name">{{$curso->modality->name}}</p></td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Objetivo</p></td>
                <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-objective">{{$curso->objetivo}}</p></td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Contenido</p></td>
                <td colspan="4" rowspan="1" class="ficha-course-content">
                    {{--needs style--}}
                    <ul>
                        @foreach ($curso->courseContent as $content)
                            <li>
                                <span>{{$content->text}}</span>
                            </li>    
                        @endforeach
                    </ul>
                </td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Duración</p></td>
                <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-duration">{{$curso->duracion}} @if($curso->duracion > 1)Horas @else Hora @endif
                </p></td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Dirigido a</p></td>
                <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-participant">{{$curso->dirigido}}</p></td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Acciones de formación previa</p></td>
                <td colspan="4" rowspan="1" class="ficha-subtitile-name-td"><p class="ficha-course-requirement">Ninguna</p></td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-course-participant-capacity-subtitle-td">
                    <p class="ficha-course-subtitle">Número de participantes</p>
                </td>
                <td colspan="1" rowspan="1" class="ficha-course-participant-capacity-minimum-td">
                    <p class="ficha-course-capacity">Mínimo</p>
                </td>
                <td colspan="1" rowspan="1">
                    <p class="ficha-course-capacity-content">{{$curso->min}}</p>
                </td>
                <td colspan="1" rowspan="1">
                    <p class="ficha-course-capacity">Máximo</p>
                </td>
                <td colspan="1" rowspan="1">
                    <p class="ficha-course-capacity-content">{{$curso->max}}</p>
                </td>
            </tr>
            
        </tbody>
    </table>
</div>

@stop

@section('modals')

@stop
