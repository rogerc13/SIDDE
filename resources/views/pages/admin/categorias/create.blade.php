@push('JS')
<script>
    function crearCategoria(url){
        document.getElementById("categoria-form").reset(); 
        $(".loader").addClass("hidden");
        $("#categoria-form").removeClass("hidden");
        $("[name=_method]").val("POST");
        $("#categoria-label").html("Nueva Área de Conocimiento");
        $("#categoria-form").attr("action", url);  
        $("#categoria-modal").modal();
    }
    
</script>
@endpush

<div class="modal fade" id="categoria-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="categoria-label"></h4>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden" method="POST" id='categoria-form'  enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">
                

                <div class="modal-body">                        

                        <div class="form-group" >
                            <div class="col-lg-6 col-md-6 col-lg-offset-3 col-md-offset-3" >
                                {{ Form::label('nombre', 'Nombre') }}
                                {{ Form::text('nombre', null , array('class' => 'form-control', 'maxlength'=>60 ,'required')) }}
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