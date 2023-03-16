 @push('JS')
<script>
    function eliminarPrograma(url){
        $(".loader").addClass("hidden");
        $("#programa-form-delete").removeClass("hidden");
        $("[name=_method]").val("DELETE");
        $("#programa-label-delete").html("Eliminar programación");
        $("#programa-form-delete").attr("action", url);  
        $("#programa-modal-delete").modal();
        
    }
    
</script>
@endpush

<div class="modal fade" id="programa-modal-delete" tabindex="-1" role="dialog" aria-labelledby="programa-modal-delete" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3 class="modal-title" id="programa-label-delete">Eliminar</h3>
            </div>
            <div class="loader text-center">
                <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Loading...</span>
            </div>

            <div class="modal-body">
                
                    <p>¿Está seguro que desea eliminar esta acción de formación programada, todos los participantes asignados seran retirados?</p>
                
            </div>

            <div class="modal-footer">
                <form class="form-horizontal hidden" method="POST" id='programa-form-delete' >
                    {!! csrf_field() !!}
                    <input type="hidden" name="_method" value="POST">
                    
                    <button class="btn btn-danger" id="btn-action" >Eliminar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>