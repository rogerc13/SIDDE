 @push('JS')
<script>
    function eliminarPCurso(url){
        $(".loader").addClass("hidden");
        $("#pcurso-form-delete").removeClass("hidden");
        $("[name=_method]").val("DELETE");
        $("#pcurso-label-delete").html("Eliminar participante");
        $("#pcurso-form-delete").attr("action", url);  
        $("#pcurso-modal-delete").modal();
        
    }
    
</script>
@endpush

<div class="modal fade" id="pcurso-modal-delete" tabindex="-1" role="dialog" aria-labelledby="pcurso-modal-delete" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3 class="modal-title" id="pcurso-label-delete">Eliminar</h3>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Loading...</span>
            </div>

            <div class="modal-body">
                
                    <p>¿Está seguro que desea eliminar el participante seleccionado?</p>
                
            </div>

            <div class="modal-footer">
                <form class="form-horizontal hidden" method="POST" id='pcurso-form-delete' >
                    {!! csrf_field() !!}
                    <input type="hidden" name="_method" value="POST">
                    
                    <button class="btn btn-danger" id="btn-action" >Eliminar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>