@push('JS')
<script>
    function detallesAccion(url){
        
        $(".loader").removeClass("hidden");
        $("#accion-form").addClass("hidden");
        $("[name=_method]").val("GET");
        //$("#accion-form").attr("action", url);
        $("#accion-label").html("Detalles acción de formación");
        $("#aceptar").addClass("hidden");

        $("#accion-form :input").prop('readonly', true);
        $( "#accion-form select" ).prop('disabled', true);
        $("#docs").addClass("hidden");
        $("#accion-aceptar").addClass("hidden");

        // $('#objetivo').data('wysihtml5').editor.toolbar.hide();
        // $('#objetivo').data('wysihtml5').editor.composer.disable();

        $('.radio-prerequisite').hide();
        $('.select-prerequisite-helper').show();

        $.get(url,function(data,status){
                
                data=JSON.parse(data);
                console.log(data);
                console.log(data[0].prerequisite[0].prerequisite.title);
                $('#codigo').val(data[0].code);

                if(data[0].prerequisite.length > 0){
                    $('#prerequisite').append(`<option>${data[0].prerequisite[0].prerequisite.title}</option>`).trigger('change');
                }else{
                    $('#prerequisite').append(`<option>No posee Prerequisito</option>`).trigger('change');
                }
                $('#titulo').val(data[0].title);
                $('#categoria_id').val(data[0].category_id).trigger("change");
                $('#modalidad_id').val(data[0].modality_id).trigger("change");
                $('#duracion').val(data[0].duration);
                $('#dirigido').val(data[0].addressed);
                $('#min').val(data[0].capacity[0].min);
                $('#max').val(data[0].capacity[0].max);
                $('#objetivo').val(data[0].objective);
                // $("#objetivo").data("wysihtml5").editor.setValue(data.objetivo);
                //$('#contenido').val(data[0].contenido);
                
                $('.content-list').html('');
                contentData = [];
                i = 0;
                data[0].content.forEach(element => {
                    contentData[i] = element.text;
                    $('.content-list').append(`<li value="${i++}" class="list-element list-group-item"><span class="list-text">${element.text}</span><i class="fa fa-remove " style="float:right"></i></li>`);
                //console.log(i++ +' '+element.text);
                });

                if(typeof data[0].file !== 'undefined'){
                    if(data[0].file.length > 0){
                        //console.log(data[0].file.length);
                        $('.read-only-docs').show();    
                        $('.no-docs').hide();
                        $('.facilitator_manual').val(data[0].file[0].path);
                        $('.participant_manual').val(data[0].file[1].path);
                        $('.course_guide').val(data[0].file[2].path);
                        $('.course_presentation').val(data[0].file[3].path);

                    }else{
                        $('.read-only-docs').hide();
                        $('.no-docs').show();
                        
                    }
                }                
                $(".loader").addClass("hidden");
                $("#accion-form").removeClass("hidden");
            });

        $("#accion-modal").modal();
        
        $("#accion-modal").on("hidden.bs.modal", function () {
            $('.course-code').parent().removeClass('has-error');
            $('.code-error-text').hide();
            //$('.create-course-form')[0].reset(); //resets input fields at closing
            $("#accion-form :input").prop('readonly', false);
            $("#accion-form select").prop('disabled', false);
            $("#accion-aceptar").removeClass("hidden");
            $("#docs").removeClass("hidden");
            // $('#objetivo').data('wysihtml5').editor.toolbar.show();
            // $('#objetivo').data('wysihtml5').editor.composer.enable();
        $('.course-list').off();
       });
    }
</script>
@endpush
