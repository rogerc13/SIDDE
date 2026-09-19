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
    // Prevent non-numeric input in phone field
        $(document).on('input', '#phone', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
</script>
@endpush

<div class="modal fade" id="participante-modal" tabindex="-1" role="dialog" aria-labelledby="participante-label" aria-hidden="true">
    <div class="modal-dialog sidde-modal" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <div class="sidde-modal-heading">
                    <span class="sidde-modal-icon"><i class="fa fa-user"></i></span>
                    <h4 class="modal-title" id="participante-label">Nuevo participante</h4>
                </div>
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
                    @include('pages.admin.partials.person-form-fields', ['showRole' => false])
                </div>

                <div class="modal-footer text-center">
                    <button type="submit" class="btn btn-primary" id="participante-aceptar"><i class="fa fa-check"></i> Guardar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar"><i class="fa fa-times"></i> Cancelar</button>
                </div>

            </form>
        </div>
    </div>
</div>