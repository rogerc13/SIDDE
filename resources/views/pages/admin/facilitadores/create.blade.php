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
    // Prevent non-numeric input in phone field
        $(document).on('input', '#phone', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
</script>
@endpush

<div class="modal fade" id="facilitador-modal" tabindex="-1" role="dialog" aria-labelledby="facilitador-label" aria-hidden="true">
    <div class="modal-dialog sidde-modal" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <div class="sidde-modal-heading">
                    <span class="sidde-modal-icon"><i class="fa fa-mortar-board"></i></span>
                    <h4 class="modal-title" id="facilitador-label">Nuevo facilitador</h4>
                </div>
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
                    @include('pages.admin.partials.person-form-fields', ['showRole' => false])
                </div>

                <div class="modal-footer text-center">
                    <button type="submit" class="btn btn-primary" id="facilitador-aceptar"><i class="fa fa-check"></i> Guardar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar"><i class="fa fa-times"></i> Cancelar</button>
                </div>

            </form>
        </div>
    </div>
</div>