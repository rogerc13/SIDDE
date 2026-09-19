@push('JS')
<script>
    function eliminarLocation(url){
        $(".loader").addClass("hidden");
        $("#location-form-delete").removeClass("hidden");
        $("[name=_method]").val("DELETE");
        $("#location-label-delete").html("Eliminar Aula");
        $("#location-form-delete").attr("action", url);
        $("#location-modal-delete").modal();
    }

    function eliminarFloor(url){
        $(".loader").addClass("hidden");
        $("#floor-form-delete").removeClass("hidden");
        $("[name=_method]").val("DELETE");
        $("#floor-label-delete").html("Eliminar Piso");
        $("#floor-form-delete").attr("action", url);
        $("#floor-modal-delete").modal();
    }

    function eliminarBuilding(url){
        $(".loader").addClass("hidden");
        $("#building-form-delete").removeClass("hidden");
        $("[name=_method]").val("DELETE");
        $("#building-label-delete").html("Eliminar Edificio");
        $("#building-form-delete").attr("action", url);
        $("#building-modal-delete").modal();
    }

</script>
@endpush

<div class="modal fade" id="location-modal-delete" tabindex="-1" role="dialog" aria-labelledby="location-modal-delete" aria-hidden="true">
    <div class="modal-dialog sidde-modal sidde-modal-danger">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <div class="sidde-modal-heading">
                    <span class="sidde-modal-icon"><i class="fa fa-trash-o"></i></span>
                    <h4 class="modal-title" id="location-label-delete">Eliminar</h4>
                </div>
            </div>
            <div class="loader text-center">
                <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Loading...</span>
            </div>
            <div class="modal-body">
                <div class="sidde-modal-alert">
                    <i class="fa fa-exclamation-triangle"></i>
                    <p>¿Está seguro que desea eliminar esta aula? Si posee sesiones asignadas, no podrá ser eliminada.</p>
                </div>
            </div>
            <div class="modal-footer">
                <form class="form-horizontal hidden" method="POST" id='location-form-delete'>
                    {!! csrf_field() !!}
                    <input type="hidden" name="_method" value="POST">
                    <button class="btn btn-danger" id="btn-action"><i class="fa fa-trash-o"></i> Eliminar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="floor-modal-delete" tabindex="-1" role="dialog" aria-labelledby="floor-modal-delete" aria-hidden="true">
    <div class="modal-dialog sidde-modal sidde-modal-danger">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <div class="sidde-modal-heading">
                    <span class="sidde-modal-icon"><i class="fa fa-trash-o"></i></span>
                    <h4 class="modal-title" id="floor-label-delete">Eliminar</h4>
                </div>
            </div>
            <div class="loader text-center">
                <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Loading...</span>
            </div>
            <div class="modal-body">
                <div class="sidde-modal-alert">
                    <i class="fa fa-exclamation-triangle"></i>
                    <p>¿Está seguro que desea eliminar este piso? Si posee aulas asignadas, no podrá ser eliminado.</p>
                </div>
            </div>
            <div class="modal-footer">
                <form class="form-horizontal hidden" method="POST" id='floor-form-delete'>
                    {!! csrf_field() !!}
                    <input type="hidden" name="_method" value="POST">
                    <button class="btn btn-danger" id="btn-action"><i class="fa fa-trash-o"></i> Eliminar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="building-modal-delete" tabindex="-1" role="dialog" aria-labelledby="building-modal-delete" aria-hidden="true">
    <div class="modal-dialog sidde-modal sidde-modal-danger">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <div class="sidde-modal-heading">
                    <span class="sidde-modal-icon"><i class="fa fa-trash-o"></i></span>
                    <h4 class="modal-title" id="building-label-delete">Eliminar</h4>
                </div>
            </div>
            <div class="loader text-center">
                <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Loading...</span>
            </div>
            <div class="modal-body">
                <div class="sidde-modal-alert">
                    <i class="fa fa-exclamation-triangle"></i>
                    <p>¿Está seguro que desea eliminar este edificio? Si posee pisos asignados, no podrá ser eliminado.</p>
                </div>
            </div>
            <div class="modal-footer">
                <form class="form-horizontal hidden" method="POST" id='building-form-delete'>
                    {!! csrf_field() !!}
                    <input type="hidden" name="_method" value="POST">
                    <button class="btn btn-danger" id="btn-action"><i class="fa fa-trash-o"></i> Eliminar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</div>
