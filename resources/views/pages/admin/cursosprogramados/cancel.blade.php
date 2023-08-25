 @push('JS')
<script>
    function cancelPrograma(url){
        $(".loader").addClass("hidden");
        $("#programa-form-cancel").removeClass("hidden");
        $("[name=_method]").val("PUT");
        $("#programa-label-cancel").html("Cancelar Programación");
        $("#programa-form-cancel").attr("action", url);  
        $("#programa-modal-cancel").modal();
        
    }
    
</script>
@endpush

<div class="modal fade" id="programa-modal-cancel" tabindex="-1" role="dialog" aria-labelledby="programa-modal-cancel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3 class="modal-title" id="programa-label-cancel">Cancelar</h3>
            </div>
            <div class="loader text-center">
                <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Loading...</span>
            </div>

            <div class="modal-body">
                
                    <p>¿Está seguro que desea cancelar esta acción de formación programada?, todos los participantes asignados serán retirados.</p>
                
            </div>

            <div class="modal-footer">
                <form class="form-horizontal hidden" method="POST" id='programa-form-cancel' >
                    {!! csrf_field() !!}
                    <input type="hidden" name="_method" value="POST">
                    
                    <button class="btn btn-danger" id="btn-action" >Aceptar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>