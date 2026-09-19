@once('JS')

    <script src="{{asset('assets/js/course-content-list.js')}}"></script>
    <!-- <script src="{{asset('assets/js/course-code-validation.js')}}"></script> -->
    <script src="{{asset('assets/js/course-modal.js')}}"></script>
    <script src="{{asset('assets/js/course-modal-prerequisite.js')}}"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

@endonce
<div class="modal fade" id="accion-modal" tabindex="-1" role="dialog" aria-labelledby="accion-label" aria-hidden="true">
    <div class="modal-dialog modal-lg sidde-modal" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="sidde-modal-heading">
                    <span class="sidde-modal-icon"><i class="fa fa-graduation-cap"></i></span>
                    <h4 class="modal-title" id="accion-label">Nueva acción de formación</h4>
                </div>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden create-course-form" action="javascript:setCourse(deleteHelper)" method="POST" id='accion-form'  enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="course-id" class="course-id">
                <div class="modal-body">
                <ul class="sidde-steps">
                    <li class="sidde-step active" data-step="0"><span class="sidde-step-icon"><i class="fa fa-info-circle"></i></span>General</li>
                    <li class="sidde-step" data-step="1"><span class="sidde-step-icon"><i class="fa fa-users"></i></span>Capacidad</li>
                    <li class="sidde-step" data-step="2"><span class="sidde-step-icon"><i class="fa fa-list-ol"></i></span>Contenido</li>
                    <li class="sidde-step" data-step="3"><span class="sidde-step-icon"><i class="fa fa-paperclip"></i></span>Documentos</li>
                </ul>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="tab-1">{{-- 1st tab --}}
                        <h5 class="sidde-section-title"><i class="fa fa-info-circle"></i> Información general</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {{ Form::label('codigo', 'Código', array('class' => 'control-label')) }}
                                    <div class="input-group">
                                        <span class="input-group-addon" aria-hidden="true"><i class="fa fa-barcode"></i></span>
                                        {{ Form::text('codigo', null , array('class' => '0-tab-input form-control course-code is-invalid', 'maxlength'=>300 ,'required','aria-describedby'=>"helpBlock",'placeholder'=>'Ej: FOR-001')) }}
                                    </div>
                                    <span id="helpBlock" class="has-error help-block code-error-text">Este código ya se encuentra asignado a otra Acción de Formación.</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label" id="prerequisite-label">Prerequisito</label>
                                    <div class="sidde-segmented" role="radiogroup" aria-labelledby="prerequisite-label">
                                        <label class="sidde-segmented-option radio-prerequisite no-prerequisite checked">
                                            <input type="radio" name="radio1" id="rd-1" value="no" checked>
                                            <i class="fa fa-ban" aria-hidden="true"></i> No posee
                                        </label>
                                        <label class="sidde-segmented-option radio-prerequisite">
                                            <input type="radio" name="radio1" id="rd-2" value="si">
                                            <i class="fa fa-link" aria-hidden="true"></i> Sí posee
                                        </label>
                                    </div>
                                    <div class="select-prerequisite-helper">
                                        <select name="prerequisite" class="select2" id="prerequisite" data-allow-clear="true">
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {{ Form::label('titulo', 'Título', array('class' => 'control-label')) }}
                                    <div class="input-group">
                                        <span class="input-group-addon" aria-hidden="true"><i class="fa fa-font"></i></span>
                                        {{ Form::text('titulo', null , array('class' => '0-tab-input form-control', 'maxlength'=>300 ,'required','placeholder'=>'Ej: Inducción a la Seguridad Laboral')) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {{ Form::label('categoria_id', 'Área de conocimiento', array('class' => 'control-label')) }}
                                    <select name="categoria_id" class="select2" id="categoria_id" data-allow-clear="true" required="true">
                                        <option></option>
                                        @foreach($categorias as $categ)
                                            @if(old('categoria_id') == $categ->id)
                                                <option value="{{$categ->id}}" selected>{{$categ->name}}</option>
                                            @else
                                                <option value="{{$categ->id}}">{{$categ->name}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {{ Form::label('modalidad_id', 'Modalidad', array('class' => 'control-label')) }}
                                    <select name="modalidad_id" class="select2" id="modalidad_id" data-allow-clear="true" required="true">
                                        <option></option>
                                        @foreach($modalities as $modality)
                                            @if(old('modalidad_id') == $modality->id)
                                                <option value="{{$modality->id}}" selected>{{$modality->name}}</option>
                                            @else
                                                <option value="{{$modality->id}}">{{$modality->name}}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {{ Form::label('duracion', 'Duración (Horas)', array('class' => 'control-label')) }}
                                    <div class="input-group">
                                        <span class="input-group-addon" aria-hidden="true"><i class="fa fa-clock-o"></i></span>
                                        {{ Form::number('duracion', null , array('class' => '0-tab-input form-control', 'min' => '1', 'required','placeholder'=>'Ej: 40')) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>{{-- end 1st tab --}}
                    <div role="tabpanel" class="tab-pane" id="tab-2"> {{-- 2nd tab --}}
                        <h5 class="sidde-section-title"><i class="fa fa-users"></i> Capacidad y participantes</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {{ Form::label('min', 'Mínimo', array('class' => 'control-label')) }}
                                    <div class="input-group">
                                        <span class="input-group-addon" aria-hidden="true"><i class="fa fa-user-plus"></i></span>
                                        {{ Form::number('min', null , array('class' => '1-tab-input form-control', 'min' => '1', 'required','placeholder'=>'Ej: 5')) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {{ Form::label('max', 'Máximo', array('class' => 'control-label')) }}
                                    <div class="input-group">
                                        <span class="input-group-addon" aria-hidden="true"><i class="fa fa-users"></i></span>
                                        {{ Form::number('max', null , array('class' => '1-tab-input form-control', 'min' => '1', 'required','placeholder'=>'Ej: 20')) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    {{ Form::label('dirigido', 'Dirigido a', array('class' => 'control-label')) }}
                                    <div class="input-group">
                                        <span class="input-group-addon" aria-hidden="true"><i class="fa fa-crosshairs"></i></span>
                                        {{ Form::text('dirigido', null , array('class' => '1-tab-input form-control', 'maxlength'=>300 ,'required','placeholder'=>'Ej: Personal administrativo y operativo')) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>{{-- end 2nd tab --}}
                    <div role="tabpanel" class="tab-pane" id="tab-3">{{-- 3rd tab --}}
                        <h5 class="sidde-section-title"><i class="fa fa-list-ol"></i> Objetivo y contenido</h5>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    {{ Form::label('objetivo', 'Objetivo', array('class' => 'control-label')) }}
                                    {{ Form::textarea('objetivo', null , array('class' => '2-tab-input form-control','maxlength'=>3000 , 'rows'=>'7', 'style'=>'resize: vertical;','required','placeholder'=>'Describa el objetivo general de la acción de formación...')) }}
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="contenido" class="control-label">Contenido</label>
                                    <div class="input-group content-input-group">
                                        <span class="input-group-addon" aria-hidden="true"><i class="fa fa-list-ol"></i></span>
                                        <input type="text" id="contenido" class="form-control content-input" placeholder="Escriba un contenido y presione Añadir...">
                                        <div class="input-group-btn">
                                            <button class="btn btn-success add-content-btn" type="button">
                                                <span class="fa fa-plus"></span> Añadir
                                            </button>
                                        </div>
                                    </div>
                                    <ul class="content-list list-group"></ul>
                                    <small class="form-text text-muted">Puede agregar, editar o eliminar los contenidos antes de guardar el curso.</small>
                                </div>
                            </div>
                        </div>
                    </div>{{-- end 3rd tab --}}
                    <div role="tabpanel" class="tab-pane" id="tab-4">{{-- 4th tab --}}
                        <h5 class="sidde-section-title"><i class="fa fa-paperclip"></i> Documentos</h5>
                        <div id="docs">
                            <div class="sidde-doc-list">
                                <div id="fileinput_manual_f" class="sidde-doc-row fileinput fileinput-new" data-provides="fileinput" data-doc-type="1">
                                    <span class="sidde-doc-icon"><i class="fa fa-file-text-o"></i></span>
                                    <div class="sidde-doc-body" data-trigger="fileinput">
                                        <span class="sidde-doc-title">Manual del Facilitador</span>
                                        <span class="sidde-doc-name fileinput-filename" id="l_manual_f"></span>
                                        <span class="sidde-doc-empty">Ningún archivo seleccionado</span>
                                    </div>
                                    <div class="sidde-doc-actions">
                                        <span class="btn btn-default btn-sm btn-file">
                                            <i class="fa fa-upload"></i>
                                            <span class="fileinput-new">Seleccionar</span>
                                            <span class="fileinput-exists">Cambiar</span>
                                            <input type="file" accept=".pdf,.txt, .doc,.docx, .xls, .xlsx" name="manual_f" id="manual_f">
                                        </span>
                                        <a href="#" id="remove_facilitator" class="btn btn-link btn-sm sidde-doc-remove fileinput-exists" data-dismiss="fileinput"><i class="fa fa-times"></i> Quitar</a>
                                    </div>
                                </div>
                                <div id="fileinput_manual_p" class="sidde-doc-row fileinput fileinput-new" data-provides="fileinput" data-doc-type="2">
                                    <span class="sidde-doc-icon"><i class="fa fa-file-text-o"></i></span>
                                    <div class="sidde-doc-body" data-trigger="fileinput">
                                        <span class="sidde-doc-title">Manual del Participante</span>
                                        <span class="sidde-doc-name fileinput-filename" id="l_manual_p"></span>
                                        <span class="sidde-doc-empty">Ningún archivo seleccionado</span>
                                    </div>
                                    <div class="sidde-doc-actions">
                                        <span class="btn btn-default btn-sm btn-file">
                                            <i class="fa fa-upload"></i>
                                            <span class="fileinput-new">Seleccionar</span>
                                            <span class="fileinput-exists">Cambiar</span>
                                            <input type="file" accept=".pdf,.txt, .doc,.docx, .xls, .xlsx" name="manual_p" id="manual_p">
                                        </span>
                                        <a href="#" id="remove_manual" class="btn btn-link btn-sm sidde-doc-remove fileinput-exists" data-dismiss="fileinput"><i class="fa fa-times"></i> Quitar</a>
                                    </div>
                                </div>
                                <div id="fileinput_guia" class="sidde-doc-row fileinput fileinput-new" data-provides="fileinput" data-doc-type="3">
                                    <span class="sidde-doc-icon"><i class="fa fa-book"></i></span>
                                    <div class="sidde-doc-body" data-trigger="fileinput">
                                        <span class="sidde-doc-title">Guía</span>
                                        <span class="sidde-doc-name fileinput-filename" id="l_guia"></span>
                                        <span class="sidde-doc-empty">Ningún archivo seleccionado</span>
                                    </div>
                                    <div class="sidde-doc-actions">
                                        <span class="btn btn-default btn-sm btn-file">
                                            <i class="fa fa-upload"></i>
                                            <span class="fileinput-new">Seleccionar</span>
                                            <span class="fileinput-exists">Cambiar</span>
                                            <input type="file" accept=".pdf,.txt, .doc,.docx, .xls, .xlsx" name="guia" id="guia">
                                        </span>
                                        <a href="#" id="remove_guide" class="btn btn-link btn-sm sidde-doc-remove fileinput-exists" data-dismiss="fileinput"><i class="fa fa-times"></i> Quitar</a>
                                    </div>
                                </div>
                                <div id="fileinput_presentacion" class="sidde-doc-row fileinput fileinput-new" data-provides="fileinput" data-doc-type="4">
                                    <span class="sidde-doc-icon"><i class="fa fa-file-powerpoint-o"></i></span>
                                    <div class="sidde-doc-body" data-trigger="fileinput">
                                        <span class="sidde-doc-title">Presentación</span>
                                        <span class="sidde-doc-name fileinput-filename" id="l_presentacion"></span>
                                        <span class="sidde-doc-empty">Ningún archivo seleccionado</span>
                                    </div>
                                    <div class="sidde-doc-actions">
                                        <span class="btn btn-default btn-sm btn-file">
                                            <i class="fa fa-upload"></i>
                                            <span class="fileinput-new">Seleccionar</span>
                                            <span class="fileinput-exists">Cambiar</span>
                                            <input type="file" accept=".pdf,.txt, .doc,.docx, .xls, .xlsx" name="presentacion" id="presentacion">
                                        </span>
                                        <a href="#" id="remove_presentation" class="btn btn-link btn-sm sidde-doc-remove fileinput-exists" data-dismiss="fileinput"><i class="fa fa-times"></i> Quitar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="read-only-docs">
                            <div class="sidde-doc-list">
                                <div class="sidde-doc-row is-empty" data-doc-type="1">
                                    <span class="sidde-doc-icon"><i class="fa fa-file-text-o"></i></span>
                                    <div class="sidde-doc-body">
                                        <span class="sidde-doc-title">Manual del Facilitador</span>
                                        <span class="sidde-doc-name">Sin documento</span>
                                    </div>
                                    <div class="sidde-doc-actions">
                                        <a href="#" class="btn btn-default btn-sm doc-download hidden"><i class="fa fa-download"></i> Descargar</a>
                                    </div>
                                </div>
                                <div class="sidde-doc-row is-empty" data-doc-type="2">
                                    <span class="sidde-doc-icon"><i class="fa fa-file-text-o"></i></span>
                                    <div class="sidde-doc-body">
                                        <span class="sidde-doc-title">Manual del Participante</span>
                                        <span class="sidde-doc-name">Sin documento</span>
                                    </div>
                                    <div class="sidde-doc-actions">
                                        <a href="#" class="btn btn-default btn-sm doc-download hidden"><i class="fa fa-download"></i> Descargar</a>
                                    </div>
                                </div>
                                <div class="sidde-doc-row is-empty" data-doc-type="3">
                                    <span class="sidde-doc-icon"><i class="fa fa-book"></i></span>
                                    <div class="sidde-doc-body">
                                        <span class="sidde-doc-title">Guía</span>
                                        <span class="sidde-doc-name">Sin documento</span>
                                    </div>
                                    <div class="sidde-doc-actions">
                                        <a href="#" class="btn btn-default btn-sm doc-download hidden"><i class="fa fa-download"></i> Descargar</a>
                                    </div>
                                </div>
                                <div class="sidde-doc-row is-empty" data-doc-type="4">
                                    <span class="sidde-doc-icon"><i class="fa fa-file-powerpoint-o"></i></span>
                                    <div class="sidde-doc-body">
                                        <span class="sidde-doc-title">Presentación</span>
                                        <span class="sidde-doc-name">Sin documento</span>
                                    </div>
                                    <div class="sidde-doc-actions">
                                        <a href="#" class="btn btn-default btn-sm doc-download hidden"><i class="fa fa-download"></i> Descargar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="no-docs">
                            <i class="fa fa-folder-open-o"></i>
                            <p>Este curso no posee documentos asignados.</p>
                        </div>
                    </div>{{-- end 4th tab --}}
                </div>
                </div>
                <div class="modal-footer" style='text-align: center;'>

                    {{-- <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar">Cancelar</button> --}}
                    <ul class="nav nav-pills" role="tablist">
                        <li role="presentation" class=""><a class="tab-button-close btn" data-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i> Cerrar</a></li>
                        <li role="presentation" class=""><a class="tab-button-back btn"><i class="fa fa-arrow-left"></i> Atrás</a></li>
                        <li role="presentation" class="active"><a class="tab-button-next btn btn-primary">Siguiente <i class="fa fa-arrow-right"></i></a></li>
                        <li role="presentation" class="">
                            <button type="submit" class="tab-submit btn btn-primary" id="accion-aceptar"><i class="fa fa-check"></i> Aceptar</button>
                        </li>
                        <li role="presentation"><a class="tab-button hidden" href="#tab-1" aria-controls="tab-1" role="tab" data-toggle="tab"></a></li>
                        <li role="presentation"><a class="tab-button hidden" href="#tab-2" aria-controls="tab-2" role="tab" data-toggle="tab"></a></li>
                        <li role="presentation"><a class="tab-button hidden" href="#tab-3" aria-controls="tab-3" role="tab" data-toggle="tab"></a></li>
                        <li role="presentation"><a class="tab-button hidden" href="#tab-4" aria-controls="tab-4" role="tab" data-toggle="tab"></a></li>
                    </ul>
                </div>

            </form>
        </div>
    </div>
</div>
