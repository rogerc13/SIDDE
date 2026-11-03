@push('JS')
<script>
    function editarLocation(url){
        $(".loader").removeClass("hidden");
        $("#location-form").addClass("hidden");
        $("#location-form-alert").addClass("hidden").text("").removeClass('alert-warning alert-danger');
        $("[name=_method]").val("PUT");
        $("#location-label").html("Editar Aula");
        $("#location-form").attr("action", url);
        $("#location_id").val(url.split('/').pop());
        $("#location-form :input").prop('readonly', false);
        $("#location-form select").prop('disabled', false);
        $("#location-aceptar").removeClass("hidden");

        $.get(url)
            .done(function(data, status){
                data = typeof data === 'string' ? JSON.parse(data) : data;
                var floor = data.floor || null;
                var buildingId = floor ? floor.building_id : null;

                populateBuildingDropdown('location_building_id');
                $('#location_building_id').val(buildingId || '');
                populateFloorDropdown('location_floor_id', buildingId);
                $('#location_floor_id').val(data.floor_id || '');
                $('#nombre').val(data.name || '');

                hideInlineCreate('building');
                hideInlineCreate('floor');
                hideInlineEdit('building');
                hideInlineEdit('floor');
                hideDeleteConfirm('building');
                hideDeleteConfirm('floor');

                if (!floor) {
                    $('#location-form-alert')
                        .removeClass('hidden')
                        .addClass('alert-warning')
                        .text('Esta aula no tiene piso asignado. Seleccione un edificio y un piso para guardarla.');
                }

                updateBuildingButtons();
                $(".loader").addClass("hidden");
                $("#location-form").removeClass("hidden");
            })
            .fail(function(){
                $(".loader").addClass("hidden");
                $("#location-form-alert")
                    .removeClass("hidden")
                    .addClass("alert-danger")
                    .text('No se pudo cargar el aula. Intente nuevamente.');
            });

        $("#location-modal").modal();

        $("#location-modal").on("hidden.bs.modal", function () {
            $("#location-form :input").prop('readonly', false);
            $("#location-aceptar").removeClass("hidden");
        });
    }

</script>
@endpush
