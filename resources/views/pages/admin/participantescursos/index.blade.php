@push('JS')
    <script src="{{asset('assets/js/evaluation.js')}}"></script>
@endpush
@extends('layouts.admin')
@section('content')

	<h2><strong>{{$cursoprogramado->course->title}}</strong></h2>
  <h3><strong>Periodo: @date($cursoprogramado->start_date)  a @date($cursoprogramado->end_date) </strong></h3>

    @include('pages.admin.participantescursos._content', [
        'cursoprogramado' => $cursoprogramado,
        'participantes' => $participantes,
        'statuses' => $statuses,
        'canEvaluate' => $canEvaluate,
    ])

    <div align="center">{!! $participantes->links() !!}</div>
    <a href="{{Auth::user()->isFacilitador() ? url("u/mis_acciones") : url("u/af_programadas")}}">
      <center><button type="button" class="btn btn-info return-button" ><i class="entypo-back" aria-hidden="true"></i> Regresar</button></center>
    </a>
@stop

@section('modals')
	@include('pages.admin.participantes.create')
  @include('pages.admin.participantescursos.update')
	@include('pages.admin.participantescursos.delete')
  @include('pages.admin.participantescursos.asignar')
@stop
