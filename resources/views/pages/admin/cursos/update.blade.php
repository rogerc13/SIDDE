@push('JS')
<script>
    function editarAccion(url){
        $(".loader").removeClass("hidden");
        //$("#accion-form").addClass("hidden");
        $('.course-code').parent().removeClass('has-error');
        $('.code-error-text').hide();
        $('.read-only-docs').hide();
        $("[name=_method]").val("PUT");
        //$("#accion-form").attr("action", url);
        $("#accion-label").html("Editar acción de formación");

        $(".fileinput-filename").empty();

        $.get(url,function(data,status){
                data=JSON.parse(data);
                //console.log(data);
                $('.course-id').val(data[0].id);
                $('.course-code').val(data[0].codigo);
                $('#titulo').val(data[0].titulo);
                $('#categoria_id').val(data[0].categoria_id).trigger("change");
                $('#modalidad_id').val(data[0].modality_id).trigger("change");
                $('#duracion').val(data[0].duracion);
                $('#dirigido').val(data[0].dirigido);
                $('#min').val(data[0].min);
                $('#max').val(data[0].max);
                $('#objetivo').val(data[0].objetivo);
                // $("#objetivo").data("wysihtml5").editor.setValue(data.objetivo);
                $('#contenido').val(data[0].contenido);
               
                //console.log(data[1]);
               
                i = 0;
                data[1].forEach(element => {
                    $('.content-list').append(`<li value="${i++}" class="list-element list-group-item">${element.text}<i class="fa fa-remove " style="float:right"></i></li>`);
                   //console.log(i++ +' '+element.text);
                });

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
