{{--
    Shared person form fields for the usuarios, facilitadores and
    participantes create modals.

    Pass `showRole` as true to render the Rol select (usuarios only).
--}}

<div class="row">
    <div class="form-group">
        <div class="col-lg-6 col-md-6">
            {{ Form::label('nombre', 'Nombre', ['class' => 'control-label']) }}
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                {{ Form::text('nombre', null, array('class' => 'form-control', 'maxlength'=>45, 'required', 'placeholder' => 'Ej: Juan')) }}
            </div>
        </div>
        <div class="col-lg-6 col-md-6">
            {{ Form::label('apellido', 'Apellido', ['class' => 'control-label']) }}
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                {{ Form::text('apellido', null, array('class' => 'form-control', 'maxlength'=>45, 'required', 'placeholder' => 'Ej: Pérez')) }}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="form-group">
        <div class="col-lg-6 col-md-6">
            {{ Form::label('email', 'Correo', ['class' => 'control-label']) }}
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
                {{ Form::email('email', null, array('class' => 'form-control', 'maxlength'=>60, 'required', 'placeholder' => 'Ej: juan.perez@pdvsa.com')) }}
            </div>
        </div>
        <div class="col-lg-6 col-md-6">
            {{ Form::label('ci', 'C.I', ['class' => 'control-label']) }}
            <div class="input-group">
                <div class="input-group-btn">
                    <select name="id_type" id="id_type" class="form-select input-group-text btn btn-default dropdown-toggle">
                        @foreach ($types as $type)
                        <option value="{{$type->id}}">{{$type->inital()}}</option>
                        @endforeach
                    </select>
                </div>
                {{ Form::number('ci', null, array('class' => 'form-control', 'min' => '0', 'maxlength'=>45, 'required', 'placeholder' => 'Ej: 12345678')) }}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="form-group">
        <div class="col-lg-6 col-md-6">
            <label for="sex">Sexo</label>
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-venus-mars"></i></span>
                <select name="sex" id="sex" class="form-control" required="true">
                    <option value="" disabled selected>Seleccione...</option>
                    <option value="Femenino">Femenino</option>
                    <option value="Masculino">Masculino</option>
                </select>
            </div>
        </div>
        <div class="col-lg-6 col-md-6">
            <label for="phone">Teléfono</label>
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                <input type="tel" class="form-control" name="phone" id="phone" maxlength="45" pattern="[0-9]+" inputmode="numeric" placeholder="Ej: 04141234567">
            </div>
        </div>
    </div>
</div>

@if (! empty($showRole))
    <div class="row">
        <div class="form-group">
            <div class="col-lg-6 col-md-6">
                {{ Form::label('rol', 'Rol', ['class' => 'control-label']) }}
                <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-shield"></i></span>
                    <select class="form-control" name="rol" id="rol">
                        @foreach($roles as $key => $value)
                            @if(old('rol') == $key)
                            <option value="{{$key}}" selected>{{$value}}</option>
                            @else
                            <option value="{{$key}}">{{$value}}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="row">
    <div class="form-group">
        <div class="col-lg-6 col-md-6">
            <label for="password">Contraseña</label>
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                <input class="form-control" name="password" type="password" id="password" maxlength="100" required="true" placeholder="Mínimo 6 caracteres">
            </div>
        </div>
        <div class="col-lg-6 col-md-6">
            <label for="password_confirmation">Confirmar contraseña</label>
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                <input class="form-control" name="password_confirmation" type="password" id="password_confirmation" maxlength="100" required="true" placeholder="Repita la contraseña">
            </div>
        </div>
    </div>
</div>
