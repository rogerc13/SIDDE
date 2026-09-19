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

<div class="modal fade" id="categoria-modal" tabindex="-1" role="dialog" aria-labelledby="categoria-label" aria-hidden="true">
    <div class="modal-dialog sidde-modal" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <div class="sidde-modal-heading">
                    <span class="sidde-modal-icon"><i class="fa fa-sitemap"></i></span>
                    <h4 class="modal-title" id="categoria-label">Nueva Área de Conocimiento</h4>
                </div>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden" method="POST" id='categoria-form'  enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">

                <div class="modal-body">
                    <div class="row">
                        <div class="form-group">
                            <div class="col-lg-6 col-md-6 col-lg-offset-3 col-md-offset-3">
                                {{ Form::label('nombre', 'Nombre', ['class' => 'control-label']) }}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-tag"></i></span>
                                    {{ Form::text('nombre', null, array('class' => 'form-control', 'maxlength'=>60, 'required', 'placeholder' => 'Ej: Formación Técnica')) }}
                                </div>
                                <small class="sidde-hint">Nombre con el que se agruparán las acciones de formación.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer text-center">
                    <button type="submit" class="btn btn-primary" id="categoria-aceptar"><i class="fa fa-check"></i> Guardar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar"><i class="fa fa-times"></i> Cancelar</button>
                </div>

            </form>
        </div>
    </div>
</div>