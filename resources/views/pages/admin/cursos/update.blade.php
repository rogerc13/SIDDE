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
                $('#duracion').val(data[0].duration);
                $('#dirigido').val(data[0].addressed);
                $('#min').val(data[0].capacity[0].min);
                $('#max').val(data[0].capacity[0].max);
                $('#objetivo').val(data[0].objective);
                // $("#objetivo").data("wysihtml5").editor.setValue(data.objetivo);
                $('#contenido').val(data[0].contenido);
               
                //console.log(data[1]);
                contentData = [];
                i = 0;
                data[1].forEach(element => {
                    contentData[i] = element.text;
                    $('.content-list').append(`<li value="${i++}" class="list-element list-group-item"><span class="list-text">${element.text}</span><i class="fa fa-remove " style="float:right"></i></li>`);
                   //console.log(i++ +' '+element.text);
                });

                $('.list-element i').on('click',function(e) { //removes the selected content from the list and array
                    contentData.splice($(this).parent().val(),1);
                    console.log(contentData);
                    let siblings = $(this).parent().siblings();
                    for(let prop in siblings){ //lowers the value of next siblings by 1
                        if(siblings[prop].value > $(this).parent().val()){
                            siblings[prop].value --;
                        }
                    }
                    $(this).parent().remove();
	            });

                $('.list-element').on('click',function(e){ //adds a textarea into the list element to edit it's content
                    if(e.target !== e.currentTarget) return; //prevents event from triggering when clicking on any of the children
                    $(this).children('.list-text').addClass('hidden'); //hides the span element that contains the old text value
                    $(this).append(`<textarea type="text" id="list-textarea-${$(this).val()}" class="form-control" value="${$(this).text()}"/>`); //adds the textarea
                    $('#list-'+$(this).val()).focus(); //focuses the cursor on the new textarea
                    
                    $('#list-textarea-'+$(this).val()).on('keyup',function(event){ //substitues the data in the list and contentData array
                        if((event.key === "Enter") || (event.key === ".")){ 
                            $(this).siblings('.list-text').removeClass('hidden').text(`${$(this).val()}`);
                            contentData[$(this).parent().val()] = $(this).val();
                            $(this).remove(); //removes the textarea element from the dom
                        }
                    });
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
