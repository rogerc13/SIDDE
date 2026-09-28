@push('JS')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(function () {
        const $modal = $('#asignar-modal');
        const $participant = $('#participante');
        const $error = $modal.find('.capacity-error-text');
        let ajaxSearchRequest = null;

        $modal.on('shown.bs.modal', function () {
            if (!$participant.data('select2')) {
                $participant.select2({
                    allowClear: true,
                    placeholder: 'Buscar por nombre, apellido o cédula',
                    minimumInputLength: 0,
                    ajax: {
                        url: '{{ url('u/af_programadas/assignList') }}',
                        type: 'GET',
                        dataType: 'json',
                        quietMillis: 250,
                        data: function (term, page) {
                            return {
                                scheduled_id: $('#curso_p_id').val(),
                                q: term,
                                page: page || 1
                            };
                        },
                        results: function (response) {
                            if (response.message) {
                                $error.text(response.message).show();
                            } else {
                                $error.empty().hide();
                            }

                            return {
                                results: response.results || [],
                                more: response.more || false
                            };
                        },
                        transport: function (options) {
                            if (ajaxSearchRequest) {
                                ajaxSearchRequest.abort();
                            }

                            ajaxSearchRequest = $.ajax(options);
                            ajaxSearchRequest.fail(function (response, status) {
                                if (status === 'abort') {
                                    return;
                                }

                                const message = response.responseJSON && response.responseJSON.message;
                                $error.text(message || 'Ocurrió un error al buscar participantes.').show();
                            });

                            return ajaxSearchRequest;
                        }
                    },
                    formatNoMatches: function () {
                        return 'No se encontraron participantes disponibles.';
                    },
                    formatSearching: function () {
                        return 'Buscando participantes...';
                    }
                });
            }

            $participant.select2('open');
        });

        $modal.on('hidden.bs.modal', function () {
            if (ajaxSearchRequest) {
                ajaxSearchRequest.abort();
                ajaxSearchRequest = null;
            }

            $participant.val('').trigger('change');
            $error.empty().hide();
        });
    });

    function asignarParticipanteLista(url, id) {
        const $modal = $('#asignar-modal');
        const $form = $('#asignar-form');
        const $error = $modal.find('.capacity-error-text');

        $form[0].reset();
        $form.attr('action', url);
        $form.find('[name="_method"]').val('POST');
        $form.find('[name="curso_p_id"]').val(id);
        $error.empty().hide();
        $form.removeClass('hidden');
        $('#asignar-label').text('Asignar participante');
        $modal.modal('show');
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
            <form class="form-horizontal hidden" method="POST" id="asignar-form">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="curso_p_id" id="curso_p_id" value="">

                <div class="modal-body">
                    <div class="form-group">
                        <label for="participante" class="control-label">Participante</label>
                        <input type="text" name="participante" class="form-control" id="participante" data-allow-clear="true" autocomplete="off" required>
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
