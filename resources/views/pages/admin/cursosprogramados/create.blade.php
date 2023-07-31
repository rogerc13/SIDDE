@push('JS')
<script>
    //$('#titulo').select2();
    $(document).ready(function() {
        $('#titulo').select2();
    });

    function programarAccion(url){
        document.getElementById("programar-form").reset();
        $('#titulo').trigger("change");
        $('#facilitador').trigger("change");
        $(".loader").addClass("hidden");
        $("#programar-form").removeClass("hidden");
        $("[name=_method]").val("POST");
        $("#programar-label").html("Programar acción de formación");
        $("#programar-form").attr("action", url);
        $("#programar-modal").modal();




        $('.dat2').datepicker({
                format: "dd-mm-yyyy"
        });

    }


</script>
@endpush

<div class="modal fade" id="programar-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="programar-label"></h4>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden" method="POST" id='programar-form'  enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">


                <div class="modal-body">
                    <div class="form-group" >

                        <div class="col-lg-12 col-md-12 titulo" >
                            <label for="titulo">Acción de Formación</label>

{{--                            <select name="titulo" class="select2 " id="titulo" data-allow-clear="true" required>--}}
                            <select name="titulo" id="titulo" data-allow-clear="true" required>
                                <option></option>
                                @isset($categoriasAcciones)
                                    @foreach($categoriasAcciones as $category)
                                        <optgroup label="{{$category->name}}">
                                                @foreach($category->courses as $af)

                                                    @if(old('titulo') == $af->id)
                                                        <option value="{{$af->id}}" selected>{{$af->title}}</option>
                                                    @else
                                                    <option value="{{$af->id}}">{{$af->title}}</option>
                                                    @endif
                                                @endforeach
                                        </optgroup>
                                    @endforeach    
                                @endisset
                            </select>
                        </div>
                    </div>
                    <div class="form-group" >

                         <div class="col-lg-12 col-md-12 facilitador" >
                            <label for="facilitador">Facilitador</label>
                            <select name="facilitador" class="select2 " id="facilitador" data-allow-clear="true" required>
                                <option></option>
                                @foreach($facilitadores as $facilitador)
                                    @if(old('facilitador') == $facilitador->id)
                                        <option value="{{$facilitador->person->facilitator->id}}" selected>{{$facilitador->person->name}} {{$facilitador->person->last_name}} C.I:{{$facilitador->person->id_number}}</option>
                                    @else
                                        <option value="{{$facilitador->person->facilitator->id}}">{{$facilitador->person->name}} {{$facilitador->person->last_name}} C.I:{{$facilitador->person->id_number}}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>


                    <div class="form-group" id="fechas_form">
                        <div class="col-lg-12 col-md-12" >
                            {{ Form::label('fecha_i', 'Fecha de Inicio') }}
                            {{ Form::text('fecha_i', null, array('class' => 'form-control input-lg dat2','required','autocomplete'=>'off')) }}
                            {{-- <label for="fecha_i">Fecha de Inicio</label>
                            <input type="date" class="form-control" name="fecha_i"> --}}
                        </div>
                    </div>
                    <div class="form-group" id="fechas_form">
                        <div class="col-lg-12 col-md-12" >
                            {{ Form::label('fecha_f', 'Fecha de Culminación') }}
                            {{ Form::text('fecha_f', null, array('class' => 'form-control dat2 input-lg','required','autocomplete'=>'off')) }}
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
