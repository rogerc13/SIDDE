@push('JS')
<script>
    function editarLocation(url){
        $(".loader").removeClass("hidden");
        $("#location-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#location-label").html("Editar Aula");
        $("#location-form").attr("action", url);

        $.get(url, function(data, status){
            data = JSON.parse(data);
            populateBuildingDropdown('location_building_id');
            $('#location_building_id').val(data.floor.building_id);
            populateFloorDropdown('location_floor_id', data.floor.building_id);
            $('#location_floor_id').val(data.floor_id);
            $('#nombre').val(data.name);
            hideInlineCreate('building');
            hideInlineCreate('floor');
            $(".loader").addClass("hidden");
            $("#location-form").removeClass("hidden");
        });

        $("#location-modal").modal();

        $("#location-modal").on("hidden.bs.modal", function () {
            $("#location-form :input").prop('readonly', false);
            $("#location-aceptar").removeClass("hidden").val('Crear Aula');
        });
    }

</script>
@endpush
