@push('JS')
<script>
    function crearUsuario(url){
        document.getElementById("usuario-form").reset(); 
        $(".loader").addClass("hidden");
        $("#usuario-form").removeClass("hidden");
        $("[name=_method]").val("POST");
        $("#usuario-label").html("Nuevo usuario");
        $("#usuario-form").attr("action", url);  
        $("#usuario-modal").modal();
    }
    
        // Prevent non-numeric input in phone field
        $(document).on('input', '#phone', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
@endpush

<div class="modal fade" id="usuario-modal" tabindex="-1" role="dialog" aria-labelledby="usuario-label" aria-hidden="true">
    <div class="modal-dialog sidde-modal" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <div class="sidde-modal-heading">
                    <span class="sidde-modal-icon"><i class="fa fa-users"></i></span>
                    <h4 class="modal-title" id="usuario-label">Nuevo usuario</h4>
                </div>
            </div>
            <div class="loader text-center" style="padding: 10px">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw" ></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden" method="POST" id='usuario-form'  enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="user_id" id="user_id" value="">

                <div class="modal-body">
                    @include('pages.admin.partials.person-form-fields', ['showRole' => true])
                </div>

                <div class="modal-footer text-center">
                    <button type="submit" class="btn btn-primary" id="usuario-aceptar"><i class="fa fa-check"></i> Guardar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar"><i class="fa fa-times"></i> Cancelar</button>
                </div>

            </form>
        </div>
    </div>
</div>