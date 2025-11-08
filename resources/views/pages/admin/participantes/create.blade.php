@push('JS')
<script>
    function crearParticipante(url){
        document.getElementById("participante-form").reset(); 
        $(".loader").addClass("hidden");
        $("#participante-form").removeClass("hidden");
        $("[name=_method]").val("POST");
        $("#participante-label").html("Nuevo participante");
        $("#participante-form").attr("action", url);  
        $("#participante-modal").modal();
    }
    
</script>
@endpush

<div class="modal fade" id="participante-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="participante-label"></h4>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden" method="POST" id='participante-form'  enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="user_id" id="user_id" value="">                

                <div class="modal-body">                        

                        <div class="form-group" >
                            <div class="col-lg-6 col-md-6" >
                                <label for="nombre">Nombre</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" maxlength="45" required value="{{ old('nombre') }}">
                            </div>   
                            <div class="col-lg-6 col-md-6" >
                                <label for="apellido">Apellido</label>
                                <input type="text" name="apellido" id="apellido" class="form-control" maxlength="45" required value="{{ old('apellido') }}">
                            </div>                    
                        </div>    

                        <div class="form-group" >
                            <div class="col-lg-6 col-md-6" >
                                <label for="email">Correo</label>
                                <input type="email" name="email" id="email" class="form-control" maxlength="60" required value="{{ old('email') }}">
                            </div>   
                            <div class="col-lg-6 col-md-6" >
                                <label for="ci">C.I</label>
                                <div class="input-group">
                                    <div class="input-group-btn">
                                        <select name="id_type" id="id_type" class="form-select input-group-text btn btn-default 
                                        dropdown-toggle">
                                            @foreach ($types as $type)
                                            <option value="{{$type->id}}">{{$type->inital()}}</option>
                                            @endforeach  
                                        </select>
                                    </div>
                                    <input type="number" name="ci" id="ci" class="form-control" min="0" maxlength="45" required value="{{ old('ci') }}">
                                </div>
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
                    <button type="submit" class="btn btn-primary" id="participante-aceptar">Aceptar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar">Cancelar</button>
                </div>

            </form>
        </div>
    </div>
</div>