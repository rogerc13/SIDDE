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

        $('#objetivo').data('wysihtml5').editor.toolbar.hide();
        $('#objetivo').data('wysihtml5').editor.composer.disable();



        $.get(url,function(data,status){
                data=JSON.parse(data);
                $('#titulo').val(data.titulo);
                $('#categoria_id').val(data.categoria_id).trigger("change");
                $('#modalidad').val(data.modalidad);
                $('#duracion').val(data.duracion);
                $('#dirigido').val(data.dirigido);
                $('#min').val(data.min);
                $('#max').val(data.max);
                //$('#objetivo').val(data.objetivo);
                $("#objetivo").data("wysihtml5").editor.setValue(data.objetivo);
                $('#contenido').val(data.contenido);
                $(".loader").addClass("hidden");
                $("#accion-form").removeClass("hidden");

            });
        $("#accion-modal").modal();

        $("#accion-modal").on("hidden.bs.modal", function () {
            $("#accion-form :input").prop('readonly', false);
            $( "#accion-form select" ).prop('disabled', false);
            $("#accion-aceptar").removeClass("hidden");
            $("#docs").removeClass("hidden");
            $('#objetivo').data('wysihtml5').editor.toolbar.show();
            $('#objetivo').data('wysihtml5').editor.composer.enable();
       });
    }
</script>
@endpush
