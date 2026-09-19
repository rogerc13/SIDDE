 @push('JS')
<script>
    function eliminarCategoria(url){
        $(".loader").addClass("hidden");
        $("#categoria-form-delete").removeClass("hidden");
        $("[name=_method]").val("DELETE");
        $("#categoria-label-delete").html("Eliminar área de conocimiento");
        $("#categoria-form-delete").attr("action", url);  
        $("#categoria-modal-delete").modal();
        
    }
    
</script>
@endpush

<div class="modal fade" id="categoria-modal-delete" tabindex="-1" role="dialog" aria-labelledby="categoria-modal-delete" aria-hidden="true">
    <div class="modal-dialog sidde-modal sidde-modal-danger">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <div class="sidde-modal-heading">
                    <span class="sidde-modal-icon"><i class="fa fa-trash-o"></i></span>
                    <h4 class="modal-title" id="categoria-label-delete">Eliminar</h4>
                </div>
            </div>
            <div class="loader text-center">
                <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Loading...</span>
            </div>

            <div class="modal-body">
                <div class="sidde-modal-alert">
                    <i class="fa fa-exclamation-triangle"></i>
                    <p>¿Está seguro que desea eliminar el área de conocimiento seleccionada? Esta acción no se puede deshacer.</p>
                </div>
            </div>

            <div class="modal-footer">
                <form class="form-horizontal hidden" method="POST" id='categoria-form-delete' >
                    {!! csrf_field() !!}
                    <input type="hidden" name="_method" value="POST">

                    <button class="btn btn-danger" id="btn-action"><i class="fa fa-trash-o"></i> Eliminar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>