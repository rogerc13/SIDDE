@push('JS')
<script>
    function editarEstado(url){

        $(".loader").removeClass("hidden");
        $("#estado-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#estado-label").html("Editar estado del participante");
        $("#estado-form").attr("action", url);



        $.get(url,function(data,status){
                data=JSON.parse(data);
                $('#estado').val(data.estado);
                $(".loader").addClass("hidden");
                $("#estado-form").removeClass("hidden");
                
            });

        $("#estado-modal").modal();

    }
    
</script>
@endpush

<div class="modal fade" id="estado-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="estado-label"></h4>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden" method="POST" id='estado-form'  enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">
                

                <div class="modal-body">                        

                        <div class="form-group" >
                            <div class="col-lg-6 col-md-6 col-lg-offset-3 col-md-offset-3" >
                                {{ Form::label('estado', 'Estado') }}
                                {{ Form::select('estado', $estados, null , array('class' => 'form-control','required')) }}
                            </div>                      
                        </div>    
                </div>     

                <div class="modal-footer" style='text-align: center;'>
                    {{ Form::submit('Aceptar', array('class' => 'btn btn-primary', 'id'=>'categoria-aceptar')) }}
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar">Cancelar</button>
                </div>

            </form>
        </div>
    </div>
</div>