@push('JS')
<script>
    function editarAccion(url){
        $(".loader").removeClass("hidden");
        $("#accion-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#accion-form").attr("action", url);
        $("#accion-label").html("Editar acción de formación");

        $(".fileinput-filename").empty();

        $.get(url,function(data,status){
                data=JSON.parse(data);

                $('.course-code').val(data.codigo);
                $('#titulo').val(data.titulo);
                $('#categoria_id').val(data.categoria_id).trigger("change");
                $('#modalidad_id').val(data.modality_id).trigger("change");
                $('#duracion').val(data.duracion);
                $('#dirigido').val(data.dirigido);
                $('#min').val(data.min);
                $('#max').val(data.max);
                $('#objetivo').val(data.objetivo);
                // $("#objetivo").data("wysihtml5").editor.setValue(data.objetivo);
                $('#contenido').val(data.contenido);
                if (data.manual_f) {
                    $('#l_manual_f').text(data.manual_f);
                }
                if (data.manual_p) {
                    $('#l_manual_p').text(data.manual_p);
                }
                if (data.guia) {
                    $('#l_guia').text(data.guia);
                }
                if (data.presentacion) {
                    $('#l_presentacion').text(data.presentacion);
                }
                $(".loader").addClass("hidden");
                $("#accion-form").removeClass("hidden");

            });
        $("#accion-modal").modal();
    }
</script>
@endpush
