@extends('layouts.admin')

@push('JS')
  <script src="{{url('assets/js/fileinput.js')}}"></script>
  <script>
    $(function () {
      // Toggle password visibility
      $(document).on('click', '.sidde-password-toggle', function () {
        var $input = $($(this).data('target'));
        var isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');
        $(this).find('i').toggleClass('fa-eye', !isPassword).toggleClass('fa-eye-slash', isPassword);
      });

      // Restrict the phone field to digits
      $(document).on('input', '#phone', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
      });
    });
  </script>
@endpush

@section('content')
<div class="sidde-profile">
  <div class="row">
    <div class="col-md-12">
      <div class="sidde-page-heading">
        <span class="sidde-page-icon"><i class="fa fa-user"></i></span>
        <div>
          <h2>Mis datos</h2>
          <p class="sidde-page-subtitle">Actualiza tu información personal, tus credenciales de acceso y tu foto de perfil.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    {{-- Edit form --}}
    <div class="col-md-12">
      <div class="sidde-profile-card">
        <div class="sidde-card-header"><i class="fa fa-pencil"></i> Editar perfil</div>
        <div class="sidde-profile-body">
          <form role="form" method="POST" enctype="multipart/form-data" action="{{ route('mis_datos_update') }}">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="user_id" value="{{ $logeado->id }}">
            {!! csrf_field() !!}

            <h5 class="sidde-section-title"><i class="fa fa-camera"></i> Foto de perfil</h5>

            <div class="row">
              <div class="col-sm-12">
                <div class="fileinput fileinput-new sidde-avatar-upload" data-provides="fileinput">
                  <div class="fileinput-new thumbnail" data-trigger="fileinput">
                    @if(!$logeado->person->avatar_path)
                      <img src="{{ asset('assets/images/photo.jpg') }}" alt="Foto de perfil">
                    @else
                      <img src="{{ asset('avatars/'.$logeado->person->avatar_path) }}" alt="Foto de perfil">
                    @endif
                  </div>
                  <div class="fileinput-preview fileinput-exists thumbnail"></div>
                  <div class="sidde-avatar-upload-body">
                    <div>
                      <span class="btn btn-primary btn-file">
                        <i class="fa fa-upload"></i>
                        <span class="fileinput-new">Seleccionar imagen</span>
                        <span class="fileinput-exists">Cambiar</span>
                        <input type="file" name="imagen" id="imagen" accept="image/*">
                      </span>
                      <a href="#" class="btn btn-default fileinput-exists" data-dismiss="fileinput"><i class="fa fa-times"></i> Quitar</a>
                    </div>
                    <small class="sidde-hint">Formatos de imagen (JPG, PNG). Se mostrará en tu perfil y en la barra lateral.</small>
                  </div>
                </div>
              </div>
            </div>

            <h5 class="sidde-section-title"><i class="fa fa-user"></i> Datos personales</h5>

            <div class="row">
              <div class="col-sm-6">
                <label for="nombre" class="control-label">Nombre</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-user"></i></span>
                  <input type="text" class="form-control" name="nombre" id="nombre" value="{{ old('nombre', $logeado->person->name) }}" maxlength="45" required="true" placeholder="Ej: Juan">
                </div>
              </div>
              <div class="col-sm-6">
                <label for="apellido" class="control-label">Apellido</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-user"></i></span>
                  <input type="text" class="form-control" name="apellido" id="apellido" value="{{ old('apellido', $logeado->person->last_name) }}" maxlength="45" required="true" placeholder="Ej: Pérez">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-sm-6">
                <label for="ci" class="control-label">C.I</label>
                <div class="input-group">
                  <div class="input-group-btn">
                    <select name="id_type" id="id_type" class="form-select input-group-text btn btn-default dropdown-toggle">
                      @foreach ($types as $type)
                        <option value="{{ $type->id }}"
                          @if (old('id_type', $logeado->person->id_type_id) == $type->id)
                            selected
                          @endif>{{ $type->inital() }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <input type="text" class="form-control" name="ci" id="ci" value="{{ old('ci', $logeado->person->id_number) }}" maxlength="45" required="true" placeholder="Ej: 12345678">
                </div>
              </div>
              <div class="col-sm-6">
                <label for="sex" class="control-label">Sexo</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-venus-mars"></i></span>
                  <select name="sex" id="sex" class="form-control">
                    <option value="Femenino" @if (old('sex', $logeado->person->sex) == 'Femenino') selected @endif>Femenino</option>
                    <option value="Masculino" @if (old('sex', $logeado->person->sex) == 'Masculino') selected @endif>Masculino</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-sm-6">
                <label for="phone" class="control-label">Teléfono</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                  <input type="tel" class="form-control" name="phone" id="phone" value="{{ old('phone', $logeado->person->phone) }}" maxlength="45" pattern="[0-9]+" inputmode="numeric" required="true" placeholder="Ej: 04141234567">
                </div>
              </div>
            </div>

            <h5 class="sidde-section-title"><i class="fa fa-lock"></i> Datos de acceso</h5>

            <div class="row">
              <div class="col-sm-6">
                <label for="email" class="control-label">Correo</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
                  <input type="email" class="form-control" name="email" id="email" value="{{ old('email', $logeado->email) }}" maxlength="60" required="true" placeholder="Ej: juan.perez@pdvsa.com">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-sm-6">
                <label for="password" class="control-label">Contraseña</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                  <input type="password" class="form-control" name="password" id="password" maxlength="100" placeholder="Nueva contraseña">
                  <span class="input-group-addon sidde-password-toggle" data-target="#password"><i class="fa fa-eye"></i></span>
                </div>
              </div>
              <div class="col-sm-6">
                <label for="password_confirmation" class="control-label">Confirmar contraseña</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                  <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" maxlength="100" placeholder="Repita la contraseña">
                  <span class="input-group-addon sidde-password-toggle" data-target="#password_confirmation"><i class="fa fa-eye"></i></span>
                </div>
              </div>
            </div>
            <small class="sidde-hint">Deje los campos de contraseña en blanco si no desea cambiarla.</small>

            <div class="sidde-form-actions">
              <a href="{{ URL::previous() }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Regresar</a>
              <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Guardar Cambios</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@stop
