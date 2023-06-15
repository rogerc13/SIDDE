@push('JS')
<script>
    function detallesAccion(url){
        
        $(".loader").removeClass("hidden");
        $("#accion-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#accion-form").attr("action", url);
        $("#accion-label").html("Detalles acción de formación");
        $("#aceptar").addClass("hidden");

        $("#accion-form :input").prop('readonly', true);
        $( "#accion-form select" ).prop('disabled', true);
        $("#docs").addClass("hidden");
        $("#accion-aceptar").addClass("hidden");

        // $('#objetivo').data('wysihtml5').editor.toolbar.hide();
        // $('#objetivo').data('wysihtml5').editor.composer.disable();



        $.get(url,function(data,status){
            
                
                data=JSON.parse(data);
                
                $('#codigo').val(data[0].codigo);
                $('#titulo').val(data[0].titulo);
                $('#categoria_id').val(data[0].categoria_id).trigger("change");
                $('#modalidad').val(data[0].modalidad);
                $('#duracion').val(data[0].duracion);
                $('#dirigido').val(data[0].dirigido);
                $('#min').val(data[0].min);
                $('#max').val(data[0].max);
                $('#objetivo').val(data[0].objetivo);
                // $("#objetivo").data("wysihtml5").editor.setValue(data.objetivo);
                $('#contenido').val(data[0].contenido);
                if(typeof data[0].course_file !== 'undefined'){
                    if(data[0].course_file.length > 0){
                        //console.log(data[0].course_file.length);
                        $('.read-only-docs').show();    
                        $('.facilitator_manual').val(data[0].course_file[0].file_path);
                        $('.participant_manual').val(data[0].course_file[1].file_path);
                        $('.course_guide').val(data[0].course_file[2].file_path);
                        $('.course_presentation').val(data[0].course_file[3].file_path);
                    }else{
                        $('.read-only-docs').hide();    
                    }
                }                
                $(".loader").addClass("hidden");
                $("#accion-form").removeClass("hidden");

            });
        $("#accion-modal").modal();

        $("#accion-modal").on("hidden.bs.modal", function () {
            $('.course-code').parent().removeClass('has-error');
            $('.create-course-form')[0].reset(); //resets input fields at closing
            $("#accion-form :input").prop('readonly', false);
            $( "#accion-form select" ).prop('disabled', false);
            $("#accion-aceptar").removeClass("hidden");
            $("#docs").removeClass("hidden");
            // $('#objetivo').data('wysihtml5').editor.toolbar.show();
            // $('#objetivo').data('wysihtml5').editor.composer.enable();
       });
    }
</script>
@endpush
