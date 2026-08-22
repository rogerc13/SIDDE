(function () {
    var feedbackTimer = null;

    function showFeedback(type, message) {
        var $feedback = $('#evaluation-feedback');
        $feedback.removeClass('hidden alert-success alert-danger alert-warning')
            .addClass('alert-' + type)
            .text(message);

        if (feedbackTimer) {
            clearTimeout(feedbackTimer);
        }
        feedbackTimer = setTimeout(function () {
            $feedback.addClass('hidden');
        }, 4000);
    }

    function evaluate($select) {
        var url = $select.data('url');
        var previousStatusId = String($select.data('current-status-id'));
        var newStatusId = String($select.val());

        if (newStatusId === previousStatusId) {
            return;
        }

        $select.prop('disabled', true);

        $.ajax({
            type: "POST",
            url: url,
            data: {
                _method: 'PUT',
                estado: newStatusId
            },
            dataType: "json"
        }).done(function () {
            $select.data('current-status-id', newStatusId);
            $select.prop('disabled', false);
            showFeedback('success', 'Evaluación guardada correctamente.');
        }).fail(function (response) {
            $select.val(previousStatusId);
            $select.prop('disabled', false);

            var message = 'No se pudo guardar la evaluación. Intente nuevamente.';
            if (response.responseJSON && response.responseJSON.message) {
                message = response.responseJSON.message;
            }
            showFeedback('danger', message);
        });
    }

    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('.participant_status').each(function () {
            var $select = $(this);
            if ($select.prop('disabled')) {
                $select.hide();
                $select.siblings('.participant_status_helper').show();
            } else {
                $select.show();
                $select.siblings('.participant_status_helper').hide();
            }
        });

        $(document).on('change', '.participant_status:not(:disabled)', function () {
            evaluate($(this));
        });
    });
})();
