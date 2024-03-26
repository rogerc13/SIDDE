@push('JS')
    
    <script src="{{asset('assets/js/course-content-list.js')}}"></script>
    <script src="{{asset('assets/js/course-code-validation.js')}}"></script>
    <script src="{{asset('assets/js/course-modal.js')}}"></script>
    <script src="{{asset('assets/js/course-modal-prerequisite.js')}}"></script>
    
{{--    <script src="{{url('assets/js/wysihtml5/wysihtml5-0.4.0pre.min.js')}}"></script>--}}
{{--    <script src="{{url('assets/js/wysihtml5/bootstrap-wysihtml5.js')}}"></script>--}}
{{-- <script src="{{asset('assets/js/fileinput.js')}}"></script> --}}

<script>
    function crearAccion(url){
        //var contentData = [];
        //console.log(contentData);
        $('.course-code').parent().removeClass('has-error');
        $('.code-error-text').hide();
        $('.read-only-docs').hide();
        document.getElementById("accion-form").reset();
        $('#categoria_id').trigger("change");
        $(".fileinput-filename").empty();
        $(".loader").addClass("hidden");
        $("#accion-form").removeClass("hidden");
        $("[name=_method]").val("POST");
        $("#accion-label").html("<h3>Nueva acción de formación</h3>");
        //$("#accion-form").attr("action", url);
        //$('#objetivo').data('wysihtml5').editor.composer.clear();
        $("#accion-modal").modal();   

        $(".no-docs").hide();
    }
</script>




@endpush
<div class="modal fade" id="accion-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="accion-label"></h4>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden create-course-form" action="javascript:setCourse()" method="POST" id='accion-form'  enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="course-id" class="course-id">
                <div class="modal-body">
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="tab-1">{{-- 1st tab --}}
                        <div class="form-group">
                            <div class="col-lg-6 col-md-6" >
                                {{ Form::label('codigo', 'Código',array('class' => 'control-label')) }}
                                {{ Form::text('codigo', null , array('class' => '0-tab-input input-lg form-control course-code is-invalid', 'maxlength'=>300 ,'required','aria-describedby'=>"helpBlock",'placeholder'=>'Ingrese el Código del Curso')) }}
                                <span id="helpBlock" class="has-error help-block code-error-text">Este código ya se encuentra asignado a otra Acción de Formación.</span>
                            </div>
                            <div class="col-lg-6 col-md-6">
                                <label for="Prerequisite" class="control-label">Prerequisito</label>
                                    <div class="radio radio-replace neon-cb-replacement checked radio-1 radio-prerequisite no-prerequisite">
                                        <label class="cb-wrapper">
                                            <input type="radio" id="rd-1" name="radio1" checked="" data-gtm-form-interact-field-id="2">
                                            <div class="checked"></div>
                                        </label>
                                        <label>No Posee Prerequisito</label>
                                    </div>
                                    <div class="radio radio-replace neon-cb-replacement radio-2 radio-prerequisite">
                                         <label class="cb-wrapper">
                                            <input type="radio" id="rd-2" name="radio1" data-gtm-form-interact-field-id="0">
                                            <div class="checked">
                                            </div>
                                        </label>
                                        <label>Si Posee Prerequisito</label> 
                                    </div>
                                <div class="select-prerequisite-helper">
                                    <select name="prerequisite" class="select2" id="prerequisite" data-allow-clear="true">
                                        
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-lg-6 col-md-6" >
                                {{ Form::label('titulo', 'Título',array('class' => 'control-label')) }}
                                {{ Form::text('titulo', null , array('class' => '0-tab-input form-control input-lg', 'maxlength'=>300 ,'required','placeholder'=>'Ingrese el Título del Curso')) }}
                            </div>
                            <div class="col-lg-6 col-md-6" >
                                {{ Form::label('categoria_id', 'Área de conocimiento',array('class' => 'control-label')) }}
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
                        <div class="form-group" >
                            <div class="col-lg-6 col-md-6" >
                                {{ Form::label('modalidad_id', 'Modalidad',array('class' => 'control-label')) }}
                                {{-- Form::text('modalidad', null , array('class' => 'form-control', 'maxlength'=>45 ,'required')) --}}
                                <select name="modalidad_id" class="select2 " id="modalidad_id" data-allow-clear="true" required="true">
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
                            <div class="col-lg-6 col-md-6" >
                                {{ Form::label('duracion', 'Duración (Horas)',array('class' => 'control-label')) }}
                                {{ Form::number('duracion', null , array('class' => '0-tab-input form-control input-lg', 'min' => '1', 'required','placeholder'=>'Ingrese la duración del Curso')) }}
                            </div>
                        </div>
                    </div>{{-- end 1st tab --}}
                    <div role="tabpanel" class="tab-pane" id="tab-2"> {{-- 2nd tab --}}
                            <h5 style="font-weight: bold; color: #444444">Numero de Participantes</h5>
                        <div class="form-group" >
                                <div class="col-lg-6 col-md-6" >
                                    {{ Form::label('min', 'Mínimo',array('class' => 'control-label')) }}
                                    {{ Form::number('min', null , array('class' => '1-tab-input form-control', 'min' => '1', 'required','placeholder'=>'Ingrese el número mínimo de participantes')) }}
                                </div>
                                <div class="col-lg-6 col-md-6" >
                                    {{ Form::label('max', 'Máximo',array('class' => 'control-label')) }}
                                    {{ Form::number('max', null , array('class' => '1-tab-input form-control', 'min' => '1', 'required','placeholder'=>'Ingrese el número máximo de participantes')) }}
                                </div>
                            </div>
                            <div class="form-group" >
                            <div class="col-lg-12" >
                                {{ Form::label('dirigido', 'Dirigido a',array('class' => 'control-label')) }}
                                {{ Form::text('dirigido', null , array('class' => '1-tab-input form-control', 'maxlength'=>300 ,'required','placeholder'=>'Ingrese a quien va dirigido el Curso')) }}
                            </div>
                        </div>
                    </div>{{-- end 2nd tab --}}
                    <div role="tabpanel" class="tab-pane" id="tab-3">{{-- 3rd tab --}}
                            <div class="form-group">
                            <div class="col-lg-12" >
                                {{ Form::label('objetivo', 'Objetivo',array('class' => 'control-label')) }}
                                {{ Form::textarea('objetivo', null , array('class' => '2-tab-input form-control','maxlength'=>3000 , 'rows'=>'7', 'style'=>'resize: none;','required','placeholder'=>'Ingrese el objetivo del Curso')) }}
                            </div>
                        </div>          
                        <div class="form-group">
                            <div class="col-lg-12" >
                                {{ Form::label('contenido', 'Contenido',array('class' => 'control-label')) }}
                                <br>
                                <a class="btn btn-default add-btn">Anadir Contenido <span class="glyphicon glyphicon-plus" aria-hidden="true"></span></a>
                                <a class="btn btn-default undo-btn">Deshacer</a>
                                <!-- <a class="btn btn-default list-btn">console</a> -->
                                <ul class="content-list list-style list-group sortable"></ul>
                                <!-- {{ Form::textarea('contenido', null , array('class' => '2-tab-input form-control course-content','maxlength'=>3000 , 'rows'=>'1', 'style'=>'resize: none;','placeholder'=>'Ingrese los contenidos del Curso')) }} -->
                            </div>
                        </div>
                    </div>{{-- end 3rd tab --}}
                    <div role="tabpanel" class="tab-pane" id="tab-4">{{-- 4th tab --}}
                            <div id="docs">
                            <div class="form-group" >
                                <div class="col-lg-6" >
                                    <label for="manual_f" class="control-label">Manual de facilitador</label>
                                    <div class="fileinput fileinput-new" data-provides="fileinput">
                                        <div class="input-group">
                                            <div class="form-control uneditable-input" data-trigger="fileinput">
                                                <i class="glyphicon glyphicon-file fileinput-exists"></i>
                                                <span class="fileinput-filename" id="l_manual_f"></span>
                                            </div>
                                            <span class="input-group-addon btn btn-default btn-file">
                                                <span class="fileinput-new">Buscar</span>
                                                <span class="fileinput-exists">Cambiar</span>
                                                <input type="file" accept=".pdf,.txt, .doc,.docx, .xls, .xlsx" name="manual_f" id="manual_f">
                                            </span>
                                            <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6" >
                                    <label for="manual_p" class="control-label">Manual de participante</label>
                                    <div class="fileinput fileinput-new" data-provides="fileinput">
                                        <div class="input-group">
                                            <div class="form-control uneditable-input" data-trigger="fileinput">
                                                <i class="glyphicon glyphicon-file fileinput-exists"></i>
                                                <span class="fileinput-filename" id="l_manual_p"></span>
                                            </div>
                                            <span class="input-group-addon btn btn-default btn-file">
                                                <span class="fileinput-new">Buscar</span>
                                                <span class="fileinput-exists">Cambiar</span>
                                                <input type="file" accept=".pdf,.txt, .doc,.docx, .xls, .xlsx" name="manual_p" id="manual_p">
                                            </span>
                                            <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" >
                                <div class="col-lg-6" >
                                    <label for="guia" class="control-label">Guía</label>
                                    <div class="fileinput fileinput-new" data-provides="fileinput">
                                        <div class="input-group">
                                            <div class="form-control uneditable-input" data-trigger="fileinput">
                                                <i class="glyphicon glyphicon-file fileinput-exists"></i>
                                                <span class="fileinput-filename" id="l_guia"></span>
                                            </div>
                                            <span class="input-group-addon btn btn-default btn-file">
                                                <span class="fileinput-new">Buscar</span>
                                                <span class="fileinput-exists">Cambiar</span>
                                                <input type="file" accept=".pdf,.txt, .doc,.docx, .xls, .xlsx" name="guia" id="guia">
                                            </span>
                                            <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6" >
                                    <label for="presentacion" class="control-label">Presentación</label>
                                    <div class="fileinput fileinput-new" data-provides="fileinput">
                                        <div class="input-group">
                                            <div class="form-control uneditable-input" data-trigger="fileinput">
                                                <i class="glyphicon glyphicon-file fileinput-exists"></i>
                                                <span class="fileinput-filename" id="l_presentacion"></span>
                                            </div>
                                            <span class="input-group-addon btn btn-default btn-file">
                                                <span class="fileinput-new">Buscar</span>
                                                <span class="fileinput-exists">Cambiar</span>
                                                <input type="file" accept=".pdf,.txt, .doc,.docx, .xls, .xlsx" name="presentacion" id="presentacion">
                                            </span>
                                            <a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="read-only-docs">
                            <div class="form-group" >
                                <div class="col-lg-6" >
                                    {{ Form::label('facilitator_manual', 'Manual del Facilitador', array('class' => 'control-label'))}}
                                    {{ Form::text('facilitator_manual', null , array('class' => 'form-control facilitator_manual','disabled'))}}
                                </div>
                                <div class="col-lg-6" >
                                    {{ Form::label('participant_manual', 'Manual del Participante', array('class' => 'control-label'))}}
                                    {{ Form::text('participant_manual', null , array('class' => 'form-control participant_manual','disabled'))}}
                                </div>
                                <div class="col-lg-6" >
                                    {{ Form::label('course_guide', 'Guia', array('class' => 'control-label'))}}
                                    {{ Form::text('course_guide', null , array('class' => 'form-control course_guide','disabled'))}}
                                </div>
                                <div class="col-lg-6" >
                                    {{ Form::label('course_presentation', 'Presentación', array('class' => 'control-label'))}}
                                    {{ Form::text('course_presentation', null , array('class' => 'form-control course_presentation','disabled'))}}
                                </div>
                            </div>
                        </div>
                        <div class="no-docs">
                            <span><h4>Este Curso No posee documentos asignados</h4></span>
                        </div>
                    </div>{{-- end 4th tab --}}
                </div>
                </div>
                <div class="modal-footer" style='text-align: center;'>
                   
                    {{-- <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar">Cancelar</button> --}}
                    <ul class="nav nav-pills" role="tablist">
                        <li role="presentation" class=""><a class="tab-button-close btn" data-dismiss="modal" aria-label="Close">Cerrar</a></li>
                        <li role="presentation" class=""><a class="tab-button-back btn">Atrás</a></li>
                        <li role="presentation" class="active"><a class="tab-button-next btn btn-primary">Siguiente</a></li>
                        <li role="presentation" class="">
                            {{ Form::submit('Aceptar', array('class' => 'tab-submit btn btn-primary', 'id'=>'accion-aceptar')) }}
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
