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
                contentData = [];
                i = 0;
                data[1].forEach(element => {
                    contentData[i] = element.text;
                    $('.content-list').append(`<li value="${i++}" class="list-element list-group-item">${element.text}<i class="fa fa-remove " style="float:right"></i></li>`);
                   //console.log(i++ +' '+element.text);
                });

                $('.list-element').on('click',function(e) { //removes the selected content from the list and array
                    contentData.splice(e.target.value,1);
                    let siblings = $(e.target).siblings();
                    for(let prop in siblings){ //lowers the value of next siblings by 1
                        if(siblings[prop].value > e.target.value){
                            siblings[prop].value --;
                        }
                    }
                    e.target.remove();
	            });
                //console.log(data[2][1].file_path)

                if(data[2].length > 0){
                    $('.fileinput').addClass('fileinput-exists').removeClass('fileinput-new');
                    $('#l_manual_f').text(data[2][0].file_path);
                    $('#l_manual_p').text(data[2][1].file_path);
                    $('#l_guia').text(data[2][2].file_path);
                    $('#l_presentacion').text(data[2][3].file_path);
                }else{
                    $('.fileinput').addClass('fileinput-new').removeClass('fileinput-exists');
                }

                $(".loader").addClass("hidden");
                $("#accion-form").removeClass("hidden");

            });
        $("#accion-modal").modal();

        
}


</script>

@endpush
