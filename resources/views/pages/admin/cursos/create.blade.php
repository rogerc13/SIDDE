@push('JS')
    <script src="{{url('assets/js/fileinput.js')}}"></script>
    <script src="{{asset('assets/js/course-content-list.js')}}"></script>
    <script src="{{asset('assets/js/course-code-validation.js')}}"></script>
    
{{--    <script src="{{url('assets/js/wysihtml5/wysihtml5-0.4.0pre.min.js')}}"></script>--}}
{{--    <script src="{{url('assets/js/wysihtml5/bootstrap-wysihtml5.js')}}"></script>--}}


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
        //$("[name=_method]").val("POST");
        $("#accion-label").html("Nueva acción de formación");
        //$("#accion-form").attr("action", url);


        //$('#objetivo').data('wysihtml5').editor.composer.clear();
        $("#accion-modal").modal();    
    }
</script>




@endpush

<div class="modal fade" id="accion-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="accion-label"></h4>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden create-course-form" action="javascript:setCourse()" method="POST" id='accion-form'  enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">


                <div class="modal-body">
                    <div class="form-group">
                        <div class="col-lg-6 col-md-6" >
                            {{ Form::label('codigo', 'Código',array('class' => 'control-label')) }}
                            {{ Form::text('codigo', null , array('class' => 'form-control course-code is-invalid', 'maxlength'=>300 ,'required','aria-describedby'=>"helpBlock")) }}
                            <span id="helpBlock" class="has-error help-block code-error-text">El codigo ya existe.</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-6 col-md-6" >
                            {{ Form::label('titulo', 'Título',array('class' => 'control-label')) }}
                            {{ Form::text('titulo', null , array('class' => 'form-control', 'maxlength'=>300 ,'required')) }}
                        </div>
                        <div class="col-lg-6 col-md-6" >
                            {{ Form::label('categoria_id', 'Área de conocimiento',array('class' => 'control-label')) }}

                            <select name="categoria_id" class="select2 " id="categoria_id" data-allow-clear="true" required="true">
                                <option></option>

                                @foreach($categorias as $categ)
                                    @if(old('categoria_id') == $categ->id)
                                        <option value="{{$categ->id}}" selected>{{$categ->nombre}}</option>
                                    @else
                                        <option value="{{$categ->id}}">{{$categ->nombre}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group" >
                        <div class="col-lg-6 col-md-6" >
                            {{ Form::label('modalidad', 'Modalidad',array('class' => 'control-label')) }}
                            {{ Form::text('modalidad', null , array('class' => 'form-control', 'maxlength'=>45 ,'required')) }}
                        </div>
                        <div class="col-lg-6 col-md-6" >
                            {{ Form::label('duracion', 'Duración (Horas)',array('class' => 'control-label')) }}
                            {{ Form::number('duracion', null , array('class' => 'form-control', 'min' => '1', 'required')) }}
                        </div>
                    </div>
                    <div class="form-group" >
                        <div class="col-lg-12" >
                            {{ Form::label('dirigido', 'Dirigido a',array('class' => 'control-label')) }}
                            {{ Form::text('dirigido', null , array('class' => 'form-control', 'maxlength'=>300 ,'required')) }}
                        </div>
                    </div>

                    <h5 style="font-weight: bold; color: #444444">Numero de Participantes</h5>
                    <div class="form-group" >
                        <div class="col-lg-6 col-md-6" >
                            {{ Form::label('min', 'Mínimo',array('class' => 'control-label')) }}
                            {{ Form::number('min', null , array('class' => 'form-control', 'min' => '1', 'required')) }}
                        </div>
                        <div class="col-lg-6 col-md-6" >
                            {{ Form::label('max', 'Máximo',array('class' => 'control-label')) }}
                            {{ Form::number('max', null , array('class' => 'form-control', 'min' => '1', 'required')) }}
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-12" >
                            {{ Form::label('objetivo', 'Objetivo',array('class' => 'control-label')) }}
                            {{ Form::textarea('objetivo', null , array('class' => 'form-control','maxlength'=>3000 , 'rows'=>'7', 'style'=>'resize: none;','required')) }}

                        </div>
                    </div>
                    <ul class="content-list"></ul>
                    <div class="form-group">
                        <div class="col-lg-12" >
                            {{ Form::label('contenido', 'Contenido',array('class' => 'control-label')) }}
                            {{ Form::textarea('contenido', null , array('class' => 'form-control course-content','maxlength'=>3000 , 'rows'=>'1', 'style'=>'resize: none;','required')) }}
                        </div>
                    </div>

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
                


                </div>

                <div class="modal-footer" style='text-align: center;'>
                    {{ Form::submit('Aceptar', array('class' => 'btn btn-primary', 'id'=>'accion-aceptar')) }}
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar">Cancelar</button>
                </div>

            </form>
        </div>
    </div>
</div>
