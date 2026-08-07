@push('JS')
<script>
    var buildings = @json($buildings);
    var floors = @json($floors);
    var csrfToken = '{{ csrf_token() }}';

    function crearLocation(url){
        document.getElementById("location-form").reset();
        $(".loader").addClass("hidden");
        $("#location-form").removeClass("hidden");
        $("[name=_method]").val("POST");
        $("#location-label").html("Crear / Editar Aula");
        $("#location-form").attr("action", url);
        populateBuildingDropdown('location_building_id');
        $("#location_floor_id").html('<option value="">Seleccione un piso</option>');
        hideInlineCreate('building');
        hideInlineCreate('floor');
        hideInlineEdit('building');
        hideInlineEdit('floor');
        hideDeleteConfirm('building');
        hideDeleteConfirm('floor');
        $("#location-modal").modal();
    }

    function populateBuildingDropdown(selectId) {
        var $select = $('#' + selectId);
        $select.html('<option value="">Seleccione un edificio</option>');
        buildings.forEach(function(building) {
            $select.append('<option value="' + building.id + '">' + building.name + '</option>');
        });
    }

    function populateFloorDropdown(selectId, buildingId) {
        var $select = $('#' + selectId);
        $select.html('<option value="">Seleccione un piso</option>');
        floors.forEach(function(floor) {
            if (floor.building_id == buildingId) {
                $select.append('<option value="' + floor.id + '">' + floor.name + '</option>');
            }
        });
    }

    $(document).on('change', '#location_building_id', function() {
        var buildingId = $(this).val();
        populateFloorDropdown('location_floor_id', buildingId);
        $('#floor-inline-error').addClass('hidden').text('');
        $('#building-dropdown-group .btn-edit').prop('disabled', !buildingId);
        $('#building-dropdown-group .btn-delete').prop('disabled', !buildingId);
        $('#floor-dropdown-group .btn-create').prop('disabled', !buildingId);
        $('#floor-dropdown-group .btn-edit').prop('disabled', true);
        $('#floor-dropdown-group .btn-delete').prop('disabled', true);
    });

    $(document).on('change', '#location_floor_id', function() {
        var hasSelection = !!$(this).val();
        $('#floor-dropdown-group .btn-edit').prop('disabled', !hasSelection);
        $('#floor-dropdown-group .btn-delete').prop('disabled', !hasSelection);
    });

    $('#location-modal').on('shown.bs.modal', function() {
        updateBuildingButtons();
    });

    $('#location-modal').on('hidden.bs.modal', function() {
        hideInlineCreate('building');
        hideInlineCreate('floor');
        hideInlineEdit('building');
        hideInlineEdit('floor');
        hideDeleteConfirm('building');
        hideDeleteConfirm('floor');
    });

    // --- Inline create ---
    function showInlineCreate(type) {
        if (type === 'floor' && !$('#location_building_id').val()) {
            $('#floor-inline-error').removeClass('hidden').text('Seleccione un edificio primero.');
            return;
        }

        hideInlineEdit(type);
        hideDeleteConfirm(type);
        var other = (type === 'building') ? 'floor' : 'building';
        hideInlineCreate(other);
        hideInlineEdit(other);
        hideDeleteConfirm(other);

        if (type === 'building') {
            $('#location_floor_id').html('<option value="">Seleccione un piso</option>');
            $('#location_floor_id').val('');
        }

        $('#' + type + '-dropdown-group').addClass('hidden');
        $('#' + type + '-inline-create').removeClass('hidden');
        $('#' + type + '-inline-input').focus();
    }

    function hideInlineCreate(type) {
        $('#' + type + '-dropdown-group').removeClass('hidden');
        $('#' + type + '-inline-create').addClass('hidden');
        $('#' + type + '-inline-input').val('');
        $('#' + type + '-inline-error').addClass('hidden').text('');
    }

    function submitInlineBuilding() {
        var name = $('#building-inline-input').val().trim();
        if (!name) {
            $('#building-inline-error').removeClass('hidden').text('El nombre es requerido.');
            return;
        }

        $('#building-inline-crear').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ url("u/ubicaciones/building") }}',
            type: 'POST',
            data: { name: name, _token: csrfToken },
            success: function(data) {
                buildings.push(data);
                populateBuildingDropdown('location_building_id');
                $('#location_building_id').val(data.id).trigger('change');
                hideInlineCreate('building');
                $('#building-inline-crear').prop('disabled', false).html('Crear');
                updateBuildingButtons();
            },
            error: function(xhr) {
                var msg = 'Error al crear edificio.';
                if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.name) {
                    msg = xhr.responseJSON.errors.name[0];
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                $('#building-inline-error').removeClass('hidden').text(msg);
                $('#building-inline-crear').prop('disabled', false).html('Crear');
            }
        });
    }

    function submitInlineFloor() {
        var name = $('#floor-inline-input').val().trim();
        var buildingId = $('#location_building_id').val();

        if (!buildingId) {
            $('#floor-inline-error').removeClass('hidden').text('Seleccione un edificio primero.');
            return;
        }
        if (!name) {
            $('#floor-inline-error').removeClass('hidden').text('El nombre es requerido.');
            return;
        }

        $('#floor-inline-crear').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ url("u/ubicaciones/floor") }}',
            type: 'POST',
            data: { name: name, building_id: buildingId, _token: csrfToken },
            success: function(data) {
                data.building_id = buildingId;
                floors.push(data);
                populateFloorDropdown('location_floor_id', buildingId);
                $('#location_floor_id').val(data.id).trigger('change');
                hideInlineCreate('floor');
                $('#floor-inline-crear').prop('disabled', false).html('Crear');
            },
            error: function(xhr) {
                var msg = 'Error al crear piso.';
                if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.name) {
                    msg = xhr.responseJSON.errors.name[0];
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                $('#floor-inline-error').removeClass('hidden').text(msg);
                $('#floor-inline-crear').prop('disabled', false).html('Crear');
            }
        });
    }

    // --- Inline edit ---
    function showInlineEdit(type) {
        var selectId = (type === 'building') ? 'location_building_id' : 'location_floor_id';
        var selectedId = $('#' + selectId).val();
        if (!selectedId) return;

        var items = (type === 'building') ? buildings : floors;
        var item = items.find(function(i) { return i.id == selectedId; });
        if (!item) return;

        hideInlineCreate(type);
        hideDeleteConfirm(type);
        var other = (type === 'building') ? 'floor' : 'building';
        hideInlineCreate(other);
        hideInlineEdit(other);
        hideDeleteConfirm(other);

        $('#' + type + '-dropdown-group').addClass('hidden');
        $('#' + type + '-inline-edit').removeClass('hidden');
        $('#' + type + '-edit-input').val(item.name).focus();
    }

    function hideInlineEdit(type) {
        $('#' + type + '-dropdown-group').removeClass('hidden');
        $('#' + type + '-inline-edit').addClass('hidden');
        $('#' + type + '-edit-input').val('');
        $('#' + type + '-edit-error').addClass('hidden').text('');
    }

    function submitEditBuilding() {
        var selectedId = $('#location_building_id').val();
        var name = $('#building-edit-input').val().trim();
        if (!name) {
            $('#building-edit-error').removeClass('hidden').text('El nombre es requerido.');
            return;
        }

        $('#building-edit-save').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ url("u/ubicaciones/building") }}/' + selectedId,
            type: 'POST',
            data: { name: name, _token: csrfToken, _method: 'PUT' },
            success: function(data) {
                var idx = buildings.findIndex(function(b) { return b.id == data.id; });
                if (idx !== -1) buildings[idx].name = data.name;
                populateBuildingDropdown('location_building_id');
                $('#location_building_id').val(data.id);
                hideInlineEdit('building');
                $('#building-edit-save').prop('disabled', false).html('Guardar');
            },
            error: function(xhr) {
                var msg = 'Error al editar edificio.';
                if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.name) {
                    msg = xhr.responseJSON.errors.name[0];
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                $('#building-edit-error').removeClass('hidden').text(msg);
                $('#building-edit-save').prop('disabled', false).html('Guardar');
            }
        });
    }

    function submitEditFloor() {
        var selectedId = $('#location_floor_id').val();
        var name = $('#floor-edit-input').val().trim();
        if (!name) {
            $('#floor-edit-error').removeClass('hidden').text('El nombre es requerido.');
            return;
        }

        $('#floor-edit-save').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ url("u/ubicaciones/floor") }}/' + selectedId,
            type: 'POST',
            data: { name: name, _token: csrfToken, _method: 'PUT' },
            success: function(data) {
                var buildingId = $('#location_building_id').val();
                var idx = floors.findIndex(function(f) { return f.id == data.id; });
                if (idx !== -1) floors[idx].name = data.name;
                populateFloorDropdown('location_floor_id', buildingId);
                $('#location_floor_id').val(data.id);
                hideInlineEdit('floor');
                $('#floor-edit-save').prop('disabled', false).html('Guardar');
            },
            error: function(xhr) {
                var msg = 'Error al editar piso.';
                if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.name) {
                    msg = xhr.responseJSON.errors.name[0];
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                $('#floor-edit-error').removeClass('hidden').text(msg);
                $('#floor-edit-save').prop('disabled', false).html('Guardar');
            }
        });
    }

    // --- Delete confirm ---
    function showDeleteConfirm(type) {
        var selectId = (type === 'building') ? 'location_building_id' : 'location_floor_id';
        var selectedId = $('#' + selectId).val();
        if (!selectedId) return;

        var items = (type === 'building') ? buildings : floors;
        var item = items.find(function(i) { return i.id == selectedId; });
        if (!item) return;

        hideInlineCreate(type);
        hideInlineEdit(type);
        var other = (type === 'building') ? 'floor' : 'building';
        hideInlineCreate(other);
        hideInlineEdit(other);
        hideDeleteConfirm(other);

        $('#' + type + '-dropdown-group').addClass('hidden');
        $('#' + type + '-delete-confirm').removeClass('hidden');
        $('#' + type + '-delete-name').text(item.name);
    }

    function hideDeleteConfirm(type) {
        $('#' + type + '-dropdown-group').removeClass('hidden');
        $('#' + type + '-delete-confirm').addClass('hidden');
        $('#' + type + '-delete-error').addClass('hidden').text('');
    }

    function submitDeleteBuilding() {
        var selectedId = $('#location_building_id').val();

        $('#building-delete-yes').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ url("u/ubicaciones/building") }}/' + selectedId,
            type: 'POST',
            data: { _token: csrfToken, _method: 'DELETE' },
            success: function() {
                buildings = buildings.filter(function(b) { return b.id != selectedId; });
                floors = floors.filter(function(f) { return f.building_id != selectedId; });
                populateBuildingDropdown('location_building_id');
                $('#location_building_id').val('').trigger('change');
                hideDeleteConfirm('building');
                $('#building-delete-yes').prop('disabled', false).html('Sí');
                updateBuildingButtons();
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.error || 'Error al eliminar.';
                $('#building-delete-error').removeClass('hidden').text(msg);
                $('#building-delete-yes').prop('disabled', false).html('Sí');
            }
        });
    }

    function submitDeleteFloor() {
        var selectedId = $('#location_floor_id').val();
        var buildingId = $('#location_building_id').val();

        $('#floor-delete-yes').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ url("u/ubicaciones/floor") }}/' + selectedId,
            type: 'POST',
            data: { _token: csrfToken, _method: 'DELETE' },
            success: function() {
                floors = floors.filter(function(f) { return f.id != selectedId; });
                populateFloorDropdown('location_floor_id', buildingId);
                $('#location_floor_id').val('').trigger('change');
                hideDeleteConfirm('floor');
                $('#floor-delete-yes').prop('disabled', false).html('Sí');
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.error || 'Error al eliminar.';
                $('#floor-delete-error').removeClass('hidden').text(msg);
                $('#floor-delete-yes').prop('disabled', false).html('Sí');
            }
        });
    }

    // --- Button state helpers ---
    function updateBuildingButtons() {
        var hasSelection = !!$('#location_building_id').val();
        $('#building-dropdown-group .btn-edit').prop('disabled', !hasSelection);
        $('#building-dropdown-group .btn-delete').prop('disabled', !hasSelection);
    }

</script>
@endpush

<div class="modal fade" id="location-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="location-label">Crear / Editar Aula</h4>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden" method="POST" id='location-form' enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">

                <div class="modal-body">
                    <div class="form-group">
                        <div class="col-lg-10 col-md-10 col-lg-offset-1 col-md-offset-1">
                            {{ Form::label('building_id', 'Edificio') }}
                            <div id="building-dropdown-group" style="display: flex; gap: 5px;">
                                {{ Form::select('building_id', [], null, ['class' => 'form-control', 'id' => 'location_building_id', 'required', 'placeholder' => 'Seleccione un edificio']) }}
                                <button type="button" class="btn btn-success btn-sm btn-create" onclick="showInlineCreate('building')" title="Crear nuevo edificio">
                                    <i class="fa fa-plus"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm btn-edit" onclick="showInlineEdit('building')" title="Editar edificio" disabled>
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-delete" onclick="showDeleteConfirm('building')" title="Eliminar edificio" disabled>
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                            <div id="building-inline-create" class="hidden" style="display: flex; gap: 5px;">
                                <input type="text" class="form-control" id="building-inline-input" placeholder="Nombre del edificio" maxlength="100">
                                <button type="button" class="btn btn-success btn-sm" id="building-inline-crear" onclick="submitInlineBuilding()">Crear</button>
                                <button type="button" class="btn btn-default btn-sm" onclick="hideInlineCreate('building')">Cancelar</button>
                            </div>
                            <div id="building-inline-edit" class="hidden" style="display: flex; gap: 5px;">
                                <input type="text" class="form-control" id="building-edit-input" placeholder="Nombre del edificio" maxlength="100">
                                <button type="button" class="btn btn-success btn-sm" id="building-edit-save" onclick="submitEditBuilding()">Guardar</button>
                                <button type="button" class="btn btn-default btn-sm" onclick="hideInlineEdit('building')">Cancelar</button>
                            </div>
                            <div id="building-delete-confirm" class="hidden" style="display: flex; gap: 5px; align-items: center;">
                                <small class="text-danger">¿Eliminar "<span id="building-delete-name"></span>"?</small>
                                <button type="button" class="btn btn-danger btn-sm" id="building-delete-yes" onclick="submitDeleteBuilding()">Sí</button>
                                <button type="button" class="btn btn-default btn-sm" onclick="hideDeleteConfirm('building')">No</button>
                            </div>
                            <div id="building-inline-error" class="text-danger hidden" style="margin-top: 5px;"><small></small></div>
                            <div id="building-edit-error" class="text-danger hidden" style="margin-top: 5px;"><small></small></div>
                            <div id="building-delete-error" class="text-danger hidden" style="margin-top: 5px;"><small></small></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-10 col-md-10 col-lg-offset-1 col-md-offset-1">
                            {{ Form::label('floor_id', 'Piso') }}
                            <div id="floor-dropdown-group" style="display: flex; gap: 5px;">
                                {{ Form::select('floor_id', [], null, ['class' => 'form-control', 'id' => 'location_floor_id', 'required', 'placeholder' => 'Seleccione un piso']) }}
                                <button type="button" class="btn btn-success btn-sm btn-create" onclick="showInlineCreate('floor')" title="Crear nuevo piso" disabled>
                                    <i class="fa fa-plus"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm btn-edit" onclick="showInlineEdit('floor')" title="Editar piso" disabled>
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm btn-delete" onclick="showDeleteConfirm('floor')" title="Eliminar piso" disabled>
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                            <div id="floor-inline-create" class="hidden" style="display: flex; gap: 5px;">
                                <input type="text" class="form-control" id="floor-inline-input" placeholder="Nombre del piso" maxlength="100">
                                <button type="button" class="btn btn-success btn-sm" id="floor-inline-crear" onclick="submitInlineFloor()">Crear</button>
                                <button type="button" class="btn btn-default btn-sm" onclick="hideInlineCreate('floor')">Cancelar</button>
                            </div>
                            <div id="floor-inline-edit" class="hidden" style="display: flex; gap: 5px;">
                                <input type="text" class="form-control" id="floor-edit-input" placeholder="Nombre del piso" maxlength="100">
                                <button type="button" class="btn btn-success btn-sm" id="floor-edit-save" onclick="submitEditFloor()">Guardar</button>
                                <button type="button" class="btn btn-default btn-sm" onclick="hideInlineEdit('floor')">Cancelar</button>
                            </div>
                            <div id="floor-delete-confirm" class="hidden" style="display: flex; gap: 5px; align-items: center;">
                                <small class="text-danger">¿Eliminar "<span id="floor-delete-name"></span>"?</small>
                                <button type="button" class="btn btn-danger btn-sm" id="floor-delete-yes" onclick="submitDeleteFloor()">Sí</button>
                                <button type="button" class="btn btn-default btn-sm" onclick="hideDeleteConfirm('floor')">No</button>
                            </div>
                            <div id="floor-inline-error" class="text-danger hidden" style="margin-top: 5px;"><small></small></div>
                            <div id="floor-edit-error" class="text-danger hidden" style="margin-top: 5px;"><small></small></div>
                            <div id="floor-delete-error" class="text-danger hidden" style="margin-top: 5px;"><small></small></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-10 col-md-10 col-lg-offset-1 col-md-offset-1">
                            {{ Form::label('nombre', 'Nombre del Aula') }}
                            {{ Form::text('nombre', null, array('class' => 'form-control', 'maxlength'=>100, 'required', 'placeholder' => 'Ej: Aula 101')) }}
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style='text-align: center;'>
                    {{ Form::submit('Crear Aula', array('class' => 'btn btn-primary', 'id'=>'location-aceptar')) }}
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
