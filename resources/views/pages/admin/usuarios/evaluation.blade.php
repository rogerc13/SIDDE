@push('JS')
    <script src="{{asset('assets/js/evaluation.js')}}"></script>
@endpush
@extends('layouts.admin')

@section('content')
    <h3>Nombre Curso</h3>
	<h3>Evaluación de Participantes</h3>
  <br>
  <br>
<div class="row">						
	<div class="col-md-12">

    <div class="panel panel-success" data-collapsed="0">  

      <div class="panel-heading">
        <div class="panel-title">
            Participantes
        </div>
      </div>
      
      <div class="panel-body with-table table-responsive">  							
    		<table class="table table-striped table-bordered table-center">
    			<thead>
    				<tr>
    					<th>Nombre</th>
    					<th>Apellido</th>
    					<th>C.I</th>
    					<th>Correo</th>					
    					<th>Evaluar</th>
    				</tr>
    			</thead>
    			
    			<tbody>
    		        @if(count($participants) == 0)
                        <tr>
                            <td colspan="7">No se han encontrado resultados...</td>
                        </tr>
                    @endif
                    @foreach($participants as $participant)
                      <tr>
                          <td>{{$participant->person->name}}</td>
                          <td>{{$participant->person->last_name}}</td>
                          <td>{{$participant->person->id_number}}</td>
                          <td>{{$participant->person->user->email}}</td>                      
                          <td>
                              <select name="participant_status" id="participant_status" class="form-control">
                                    @foreach ($statuses as $status)
                                        <option participant_id="{{$participant->id}}" value="{{$status->id}}" 
                                            @if ($status->id == $participant->participant_status_id)
                                            selected
                                        @endif>
                                        <span>{{$status->name}}</span></option>
                                    @endforeach
                              </select>
                              
                          </td>
                      </tr>
                    @endforeach	
    			</tbody>
    		</table>
      </div>
</div>
@endsection