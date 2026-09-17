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
                @if(!Auth::user()->isParticipante())
                <a class="btn btn-default {{(isset($files[0])) ? '' : 'disabled'}}" style='margin-right:5px' href="{{url('download/'.$curso->id.'/1')}}" role="button"><i class="fa fa-file-pdf-o"></i> Manual de Facilitador</a>
                @endif
                <a class="btn btn-default {{(isset($files[1])) ? '' : 'disabled'}}"  style='margin-right:5px' href="{{url('download/'.$curso->id.'/2')}}" role="button"><i class="fa fa-file-pdf-o"></i> Manual de Participante</a>
                @if(!Auth::user()->isParticipante())
                <a class="btn btn-default {{(isset($files[2])) ? '' : 'disabled'}}"  style='margin-right:5px' href="{{url('download/'.$curso->id.'/3')}}" role="button"><i class="fa fa-file-pdf-o"></i> Guia</a>
                <a class="btn btn-default {{(isset($files[3])) ? '' : 'disabled'}}" href="{{url('download/'.$curso->id.'/4')}}" role="button"><i class="fa fa-file-pdf-o"></i> Presentacion</a>
                @endif
            </div>
        </div>

    </div>

    @include('pages.public.partials.ficha_tecnica_body', ['curso' => $curso])

</div>


@stop

@section('modals')

@stop
