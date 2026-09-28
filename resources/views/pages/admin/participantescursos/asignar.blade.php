@push('JS')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#asignar-modal').on('hidden.bs.modal', function () {
        $('#participante').empty().append(new Option('', '')).val('').trigger('change');
        $('.capacity-error-text').empty().hide();
    });

    function asignarParticipanteLista(url, id) {
        const $modal = $('#asignar-modal');
        const $form = $('#asignar-form');
        const $participant = $('#participante');
        const $loader = $modal.find('.loader');
        const $error = $modal.find('.capacity-error-text');

        $form[0].reset();
        $form.attr('action', url);
        $form.find('[name="_method"]').val('POST');
        $form.find('[name="curso_p_id"]').val(id);
        $participant.empty().append(new Option('', '')).val('').trigger('change');
        $error.empty().hide();
        $form.addClass('hidden');
        $loader.removeClass('hidden');
        $('#asignar-label').text('Asignar participante');
        $modal.modal('show');

        $.ajax({
            type: 'POST',
            url: '{{ url('u/af_programadas/assignList') }}',
            data: { scheduled_id: id },
            dataType: 'json',
            success: function (response) {
                if (!response.success) {
                    $error.text(response.message || 'No fue posible cargar los participantes disponibles.').show();
                    return;
                }

                if (!response.list || response.list.length === 0) {
                    $error.text('No hay participantes disponibles para asignar a esta acción de formación.').show();
                    return;
                }

                response.list.forEach(function (person) {
                    const idType = person.id_type_id == 1 ? 'V' : 'E';
                    const idNumber = String(person.id_number).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    const option = new Option(person.name + ' ' + person.last_name + ' C.I: ' + idType + '-' + idNumber, person.id);

                    $(option).attr('personid', person.id);
                    $participant.append(option);
                });

                $participant.val('').trigger('change');
            },
            error: function (response) {
                const message = response.responseJSON && response.responseJSON.message;
                $error.text(message || 'Ocurrió un error al cargar los participantes disponibles.').show();
            },
            complete: function () {
                $loader.addClass('hidden');
                $form.removeClass('hidden');
            }
        });
    }
</script>
@endpush

<div class="modal fade" id="asignar-modal" tabindex="-1" role="dialog" aria-labelledby="asignar-label" aria-hidden="true">
    <div class="modal-dialog sidde-modal" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <div class="sidde-modal-heading">
                    <span class="sidde-modal-icon"><i class="fa fa-user-plus" aria-hidden="true"></i></span>
                    <h4 class="modal-title" id="asignar-label">Asignar participante</h4>
                </div>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden" method="POST" id="asignar-form">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="curso_p_id" id="curso_p_id" value="">

                <div class="modal-body">
                    <div class="form-group">
                        <label for="participante" class="control-label">Participante</label>
                        <select name="participante" class="select2 form-control" id="participante" data-allow-clear="true" required>
                            <option value=""></option>
                        </select>
                        <span class="has-error help-block capacity-error-text" role="alert" aria-live="polite"></span>
                    </div>
                </div>

                <div class="modal-footer text-center">
                    <button type="submit" class="btn btn-primary" id="asignar-aceptar"><i class="fa fa-check" aria-hidden="true"></i> Asignar</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar"><i class="fa fa-times" aria-hidden="true"></i> Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
