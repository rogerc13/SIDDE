$(document).ready(function(){
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });
    $('.select-prerequisite-helper').hide();

    // Bound on the option (not the radio) so it stays clickable even while the
    // form inputs are disabled in the create wizard.
    $('.sidde-segmented-option').on('click', function(){
        var option = $(this);

        if(option.hasClass('checked')){
            return;
        }

        $('.sidde-segmented-option').removeClass('checked');
        option.addClass('checked');
        option.find('input[type=radio]').prop('checked', true);

        if(option.hasClass('no-prerequisite')){
            // "No posee": keep the selector hidden and clear any previous choice
            $('.select-prerequisite-helper').hide();
            $('#prerequisite').val('').change();
            return;
        }

        // "Sí posee": reveal the selector and load the selectable courses
        $.ajax({
            type: "POST",
            url: "prerequisite",
            dataType: 'JSON',
            success: function(response){
                $(".sidde-segmented").hide();
                $(".select-prerequisite-helper").show();
                $("#prerequisite").html('');
                response.courses.forEach(function (element){
                    $('#prerequisite').append(`<option value="${element.code}">
                                                        ${element.code} |
                                                        ${element.title}
                                                        </option>`);
                });
                $("#prerequisite").append(
                    `<option value="">No Posee Prerequisito</option>`
                );
            },
            error: function(response){
                console.log(response);
            }
        });
    });

    // reset the prerequisite control whenever any modal closes
    $('.modal').on('hidden.bs.modal',function(){
        $(".select-prerequisite-helper").hide();
        $('.sidde-segmented').show();
        $('.sidde-segmented-option').removeClass('checked');
        $('.no-prerequisite').addClass('checked');
        $('#rd-1').prop('checked', true);
        $('#rd-2').prop('checked', false);
    });

})
