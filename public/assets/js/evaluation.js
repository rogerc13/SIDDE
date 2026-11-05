(function () {
    var feedbackTimer = null;
    var evaluationActive = false;

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

    function badgeClassForStatus(statusId) {
        switch (String(statusId)) {
            case '1':
                return 'success';
            case '2':
                return 'danger';
            case '3':
                return 'info';
            case '4':
                return 'default';
            case '5':
                return 'warning';
            default:
                return 'secondary';
        }
    }

    function setEvaluateButtonLabel(label) {
        $('.evaluate-button').find('span').text(' ' + label);
    }

    function setRowReadOnly($select) {
        $select.prop('disabled', true).hide();
        $select.siblings('.participant_status_helper').show();
    }

    function enterEvaluationMode() {
        evaluationActive = true;

        $('.participant_status').each(function () {
            var $select = $(this);
            $select.prop('disabled', false).show();
            $select.siblings('.participant_status_helper').hide();
        });

        setEvaluateButtonLabel('Finalizar Evaluación');
    }

    function exitEvaluationMode() {
        evaluationActive = false;

        $('.participant_status').each(function () {
            setRowReadOnly($(this));
        });

        setEvaluateButtonLabel('Evaluar Participantes');
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

            var $helper = $select.siblings('.participant_status_helper');
            $helper.text($select.find('option:selected').text());
            $helper.attr('class', 'participant_status_helper badge badge-' + badgeClassForStatus(newStatusId));

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

        var $evaluateButton = $('.evaluate-button');
        var canStartEvaluation = $evaluateButton.length > 0 && !$evaluateButton.hasClass('is-blocked');

        $('.participant_status').each(function () {
            if ($(this).prop('disabled')) {
                setRowReadOnly($(this));
            }
        });

        if (canStartEvaluation) {
            $evaluateButton.on('click', function (event) {
                event.preventDefault();

                if (evaluationActive) {
                    exitEvaluationMode();
                } else {
                    enterEvaluationMode();
                }
            });
        }

        $(document).on('change', '.participant_status', function () {
            if (!evaluationActive) {
                return;
            }

            evaluate($(this));
        });
    });
})();
