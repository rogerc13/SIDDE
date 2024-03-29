@extends('layouts.admin')

@push('JS')
  <script src="{{url('assets/js/fileinput.js')}}"></script>
@endpush

@section('content')
	
	<h2>Mis datos</h2>


<div class="row">						
	<div class="col-md-12">								
        <div class="panel panel-info" data-collapsed="0">
        
          <div class="panel-heading">
            <div class="panel-title">
              Editar perfil
            </div>
            

          </div>
          
          <div class="panel-body">
            
            <form role="form" class="form-horizontal form-groups-bordered" method="POST" enctype="multipart/form-data" action="{{ route('mis_datos_update') }}">
              <input type="hidden" name="_method" value="PUT">
              <input type="hidden" name="user_id" value="{{$logeado->id}}">
              {!! csrf_field() !!}
              
      
              <div class="form-group">
                <label for="nombre" class="col-sm-3 control-label">Nombre</label>
                
                <div class="col-sm-5">
                  <input type="text" class="form-control" name="nombre" id="nombre"  value="{{Auth::User()->person->name}}" maxlength="45" required="true">
                </div>
              </div>       


              <div class="form-group">
                <label for="apellido" class="col-sm-3 control-label">Apellido</label>
                
                <div class="col-sm-5">
                  <input type="text" class="form-control" name="apellido" id="apellido" value="{{Auth::User()->person->last_name}}" maxlength="45" required="true">
                </div>
              </div>


              <div class="form-group">
                <label for="email" class="col-sm-3 control-label">Correo</label>
                
                <div class="col-sm-5">
                  <input type="email" class="form-control" name="email" id="email" value="{{Auth::User()->email}}" maxlength="60" required="true">
                </div>
              </div>

              <div class="form-group">
                <label for="ci" class="col-sm-3 control-label">C.I</label>
                
                <div class="col-sm-5">
                  <input type="text" class="form-control" name="ci" id="ci"  min="0" value="{{Auth::User()->person->id_number}}" maxlength="45" required="true">
                </div>
              </div>
              <div class="form-group">
                <label for="sex" class="col-sm-3 control-label">Sexo</label>
                <div class="col-sm-5">
                  <select name="sex" id="sex" class="form-control">
                    <option value="Femenino" @if(Auth::User()->person->sex == 'Femenino') selected @endif>Femenino</option>
                    <option value="Masculino" @if(Auth::User()->person->sex == 'Masculino') selected @endif>Masculino</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label for="phone" class="col-sm-3 control-label">Teléfono</label>
                
                <div class="col-sm-5">
                  <input type="phone" class="form-control" name="phone" id="phone"  min="0" value="{{Auth::User()->person->phone}}" maxlength="45" required="true">
                </div>
              </div>


              <div class="form-group">
                <label for="password" class="col-sm-3 control-label">Contraseña</label>
                
                <div class="col-sm-5">
                  <input type="password" class="form-control" name="password" id="password"  maxlength="100">
                </div>
              </div>


              <div class="form-group">
                <label for="password_confirmation" class="col-sm-3 control-label">Confirmar contraseña</label>
                
                <div class="col-sm-5">
                  <input type="password" class="form-control" name="password_confirmation" id="password_confirmation"  maxlength="100">
                </div>
              </div>

              
              <div class="form-group">
                <label for="imagen" class="col-sm-3 control-label">Imagen</label>
                
                <div class="col-sm-5">
                  <div class="fileinput fileinput-new" data-provides="fileinput">
                    <div class="fileinput-new thumbnail" style="width: 200px; height: 150px;" data-trigger="fileinput">
                      @if(!$logeado->person->avatar_path)
                        <img src="{{asset('assets/images/photo.jpg')}}" alt="...">
                      @else
                        <img src="{{asset('avatars/'.$logeado->person->avatar_path)}}" alt="...">
                      @endif
                    </div>
                    <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 150px"></div>
                    <div>
                      <span class="btn btn-white btn-file">
                        <span class="fileinput-new">Seleccionar imagen</span>
                        <span class="fileinput-exists">Cambiar</span>
                        <input type="file" name="imagen" id="imagen" accept="image/*">
                      </span>
                      <a href="#" class="btn btn-orange fileinput-exists" data-dismiss="fileinput">Quitar</a>
                    </div>
                  </div>
                </div> 
              </div> 

              
              <div class="form-group">
                <div class="col-sm-offset-3 col-sm-5">
                  <button type="submit" class="btn btn-default">Aceptar</button>
                </div>
              </div>
            </form>
            
          </div>
        
        </div>	
	</div>			
</div>
@stop
