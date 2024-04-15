@extends('layouts.admin')
@section('content')
@push('JS')
    <script src="{{asset('assets/js/ficha-tecnica.js')}}"></script>
@endpush

<div class="container-fluid">

    <div class="hidden-print" style='margin-bottom:5px'>
        
        <div class="btn-group" role='group'>
            <button class="btn btn-blue" style='margin-right:5px' onclick="goBack()" role="button"><i class="entypo-back" aria-hidden="true"></i> Regresar</button>
            <button class="btn btn-success save-ficha" style='margin-right:5px' href="" role="button"><i class="fa fa-file-pdf-o"></i> Imprimir Ficha Técnica</button>        
            <button class="btn btn-default course-files" data-toggle='collapse' href="#download-collapse" aria-expanded='false' aria-controls='collapse-example' role="button"><i class="entypo-download"></i> Descargar Documentos del Curso</button>
        </div>

        <div class="collapse download-collapse" id="download-collapse">
            <div class="btn-group row course-file-btn" style='margin:10px 0 10px 0'>
                <a class="btn btn-default {{(isset($files[0])) ? '' : 'disabled'}}" style='margin-right:5px' href="{{url('download/'.$curso->id.'/1')}}" role="button"><i class="fa fa-file-pdf-o"></i> Manual de Facilitador</a>
                <a class="btn btn-default {{(isset($files[1])) ? '' : 'disabled'}}"  style='margin-right:5px' href="{{url('download/'.$curso->id.'/2')}}" role="button"><i class="fa fa-file-pdf-o"></i> Manual de Usuario</a>
                <a class="btn btn-default {{(isset($files[2])) ? '' : 'disabled'}}"  style='margin-right:5px' href="{{url('download/'.$curso->id.'/3')}}" role="button"><i class="fa fa-file-pdf-o"></i> Guia</a>
                <a class="btn btn-default {{(isset($files[3])) ? '' : 'disabled'}}" href="{{url('download/'.$curso->id.'/4')}}" role="button"><i class="fa fa-file-pdf-o"></i> Presentacion</a>
                
            </div>
        </div>

    </div>
    <div class="row ">
        <div class="col-md-12">
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
                    <td colspan="4" class="no-border-top"><p class="ficha-course-code">{{$curso->code}}</p></td>
                </tr>
                <tr>
                    <th scope="row" colspan="5" class="no-border-top"><p class="ficha-course-title">{{$curso->title}}</p></th>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Área de conocimiento</p></td>
                    <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-categoria-name">{{$curso->category->name}}</p></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Sub-área de conocimiento</p></td>
                    <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-sub-area ficha-course-categoria-name">{{$curso->category->name}}</p></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Modalidad</p></td>
                    <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-modalidad ficha-course-categoria-name">{{$curso->modality->name}}</p></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Objetivo</p></td>
                    <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-objective">{{$curso->objective}}</p></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Contenido</p></td>
                    <td colspan="4" rowspan="1" class="ficha-course-content">
                        {{--needs style--}}
                        <ul>
                            @foreach ($curso->content as $content)
                                <li>
                                    <span class="list-content-span">{{$content->text}}</span>
                                </li>    
                            @endforeach
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Duración</p></td>
                    <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-duration">{{$curso->duration}} @if($curso->duration > 1)Horas @else Hora @endif
                    </p></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Dirigido a</p></td>
                    <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-participant">{{$curso->addressed}}</p></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Acciones de formación previa</p></td>
                    <td colspan="4" rowspan="1" class="ficha-subtitile-name-td"><p class="ficha-course-requirement">
                        @if (isset($curso->prerequisite[0]) && ($curso->prerequisite[0]->prerequisite_id != null)) 
                            {{$curso->prerequisite[0]->courseName()}}
                        @else 
                            Ninguna                    
                        @endif
                    </p></td>
                </tr>
                <tr>
                    <td colspan="1" rowspan="1" class="ficha-course-participant-capacity-subtitle-td">
                        <p class="ficha-course-subtitle">Número de participantes</p>
                    </td>
                    <td colspan="1" rowspan="1" class="ficha-course-participant-capacity-minimum-td">
                        <p class="ficha-course-capacity">Mínimo</p>
                    </td>
                    <td colspan="1" rowspan="1">
                        <p class="ficha-course-capacity-content">{{$curso->capacity[0]->min}}</p>
                    </td>
                    <td colspan="1" rowspan="1">
                        <p class="ficha-course-capacity">Máximo</p>
                    </td>
                    <td colspan="1" rowspan="1">
                        <p class="ficha-course-capacity-content">{{$curso->capacity[0]->max}}</p>
                    </td>
                </tr>
                
            </tbody>
        </table>
        </div>  
</div>
</div>


@stop

@section('modals')

@stop
