@push('JS')
<script>
    function crearFacilitador(url){
        document.getElementById("facilitador-form").reset(); 
        $(".loader").addClass("hidden");
        $("#facilitador-form").removeClass("hidden");
        $("[name=_method]").val("POST");
        $("#facilitador-label").html("Nuevo facilitador");
        $("#facilitador-form").attr("action", url);  
        $("#facilitador-modal").modal();
    }
    
</script>
@endpush

<div class="modal fade" id="facilitador-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="facilitador-label"></h4>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden" method="POST" id='facilitador-form'  enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="user_id" id="user_id" value="">                

                <div class="modal-body">                        

                        <div class="form-group" >
                            <div class="col-lg-6 col-md-6" >
                                {{ Form::label('nombre', 'Nombre') }}
                                {{ Form::text('nombre', null , array('class' => 'form-control', 'maxlength'=>45 ,'required')) }}
                            </div>   
                            <div class="col-lg-6 col-md-6" >
	                            {{ Form::label('apellido', 'Apellido') }}
	                            {{ Form::text('apellido',null ,array('class' => 'form-control', 'maxlength'=>45,'required')) }}
                            </div>                    
                        </div>    

                        <div class="form-group" >
                            <div class="col-lg-6 col-md-6" >
                                {{ Form::label('email', 'Correo') }}
                                {{ Form::email('email', null , array('class' => 'form-control','maxlength'=>60 ,'required')) }}
                            </div>   
                            <div class="col-lg-6 col-md-6" >
                                {{ Form::label('ci', 'C.I') }}
                                {{ Form::number('ci', null, array('class' => 'form-control', 'min' => '0', 'maxlength'=>45,'required')) }}
                            </div>                    
                        </div>

                        <div class="form-group" >
                            <div class="col-lg-6 col-md-6" >
                                <label for="password">Contraseña</label>
                                <input class="form-control" name="password" type="password" id="password"  maxlength="100" required="true">
                            </div>   
                            <div class="col-lg-6 col-md-6" >
                                <label for="password_confirmation">Confirmar contraseña</label>
                                <input class="form-control" name="password_confirmation" type="password" id="password_confirmation"  maxlength="100" required="true">
                            </div>                    
                        </div>



                </div>     

                <div class="modal-footer" style='text-align: center;'>
                    {{ Form::submit('Aceptar', array('class' => 'btn btn-primary', 'id'=>'facilitador-aceptar')) }}
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar">Cancelar</button>
                </div>

            </form>
        </div>
    </div>
</div>