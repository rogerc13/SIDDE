function tabSwitch(tabIndex){
    if(tabIndex === 0){
        $('.tab-button:eq(0)').tab('show');
        $('.tab-button-back').hide();
        $('.tab-button-close').show();
        tabCheck('0');
    }else if(tabIndex === 1){
        $('.tab-button:eq(1)').tab('show');
        $('.tab-button-back').show();
        $('.tab-button-close').hide();
        tabCheck('1');
    }else if(tabIndex === 2){
        $('.tab-button:eq(2)').tab('show');
        $('.tab-button-next').show();
        $('.tab-submit').hide();
        tabCheck('2');
    }else if(tabIndex === 3){
        $('.tab-button:eq(3)').tab('show');
        $('.tab-button-next').hide();
        $('.tab-submit').show();
        console.log('tab switch');
        //tabSwitch(tabIndex);
        
    }else if(tabIndex > 3){
        tabIndex = 2;
        tabSwitch(tabIndex);
    }
}

function tabCheck(a){
    let inputElement = "." + a + "-tab-input";
    $(inputElement).each(function () {
        if ($(this).val() !== "") {
            $(".tab-button-next").show();
            $(".tab-button-next").removeClass("disabled");
        } else {
            $(".tab-button-next").addClass("disabled");
        }
    });

    $(inputElement).off().on("input", function () {
        //triggers on every input element of the modal
        console.log("input event");
        console.log($(this).val());
        $(inputElement).each(function () {
            if ($(this).val() !== "") {
                $(".tab-button-next").show();
                $(".tab-button-next").removeClass("disabled");
            } else {
                $(".tab-button-next").addClass("disabled");
            }
        });

        //CREATE MODAL
        if (
            (($("#min").val() == "") ||
            ($("#min").val() == "")) &&
            (($("#accion-label").text() === "Nueva acción de formación") &&
            (a == '1'))
        ) {
            $(".tab-button-next").addClass("disabled");
            console.log("min max not set");
        }
        //END CREATE MODAL
    });

    if((a == '2') && ($('#contenido').val() == '') && ($('#objetivo').val() != '')){
        $('.tab-button-next').removeClass('disabled');
        console.log('empty content');
    }
    
    //DETAILS MODAL
    if((a == '0')  && $('#accion-label').text() === 'Detalles Acción de formación'){
        $(".tab-button-next").removeClass("disabled");
        console.log('enabled Details modal');
    }

    if((a == '2') && $('#accion-label').text() === 'Detalles Acción de formación'){
        $('.tab-button-next').removeClass('disabled');
        console.log('enabled Details modal');
    }

    if ((a == "3") && ($("#accion-label").text() === "Detalles Acción de formación")) {
        $(".tab-button-close").show(); 
    }
    //END DETAILS MODAL

    console.log(inputElement);
        
}//end tabCheck

function draggableCheck(){ //checks if content list items can be draggable
    if(($("#accion-label").text() === "Nueva acción de formación") || ($('#accion-label').text() === "Editar acción de formación")){
        $('.sortable').sortable({
            disabled: false
        });
        $(".sortable").sortable({
            connectWith: ".sortable"
            });
        $(".sortable").disableSelection();
    }else{
        $('.sortable').sortable({
            disabled: false
        });
        $('.sortable').sortable({
            disabled: true
        });
    }
    //enables list elements to be sortable
}//end draggable check

function listButtonsCheck(){
    if(($("#accion-label").text() === "Nueva acción de formación") || ($('#accion-label').text() === "Editar acción de formación")){
        $('.add-btn').show();
    }else{
        $('.add-btn').hide();
    }
}//end list buttons check

function setCourse(){
    let contentData = [];

    $(".content-list li").each(function() { //create content list data array
        contentData.push($(this).text().trim())
    });
    
    let formData = new FormData ($('#accion-form').get(0));
    formData.append('content_data',contentData);
    method = formData.get('_method');

    if(method == 'PUT'){
        console.log("method put");
        newUrl = "acciones_formacion/"+formData.get('course-id');
    }else{
        console.log("method POST");
        newUrl = "acciones_formacion/";
    }

    $.ajax({       
        data:formData,
        type:"POST",
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf_token"]').attr('content')},
        url: newUrl,
        dataType: "json",
        contentType: false,
        processData: false,
        success: function (response){
            console.log('success tree');
            console.log(response);
            success = true;
            method = '';
            window.location = "acciones_formacion/onSubmitAlert/"+success;
        },
        error: function (response){
            console.log('error tree');
            success = false;
            console.log(response);
            method = '';
            window.location = "acciones_formacion/onSubmitAlert/"+success;
        }
    });//end ajax call
}

function crearAccion(url){
    $('.course-code').parent().removeClass('has-error');
    $('.code-error-text').hide();
    $('.read-only-docs').hide();
    document.getElementById("accion-form").reset();
    $('#categoria_id').trigger("change");
    $(".fileinput-filename").empty();
    $(".loader").addClass("hidden");
    $("#accion-form").removeClass("hidden");
    $("[name=_method]").val("POST");
    $("#accion-label").html("<h3>Nueva acción de formación</h3>");
    $("#accion-modal").modal();   
    $(".no-docs").hide();
}// CREATE MODAL / CREAR ACCION DE FORMACION

function detallesAccion(url){
        
    $(".loader").removeClass("hidden");
    $("#accion-form").addClass("hidden");
    $("[name=_method]").val("GET");
    $("#accion-label").html("Detalles Acción de formación");
    $("#aceptar").addClass("hidden");

    $("#accion-form :input").prop('readonly', true);
    $( "#accion-form select" ).prop('disabled', true);
    $("#docs").addClass("hidden");
    $("#accion-aceptar").addClass("hidden");

    $('.radio-prerequisite').hide();
    $('.select-prerequisite-helper').show();

    $.get(url,function(data,status){
            
            data=JSON.parse(data);
            console.log(data);

            $('#codigo').val(data[0].code);
            $('#prerequisite').html('');
            if(data[0].prerequisite.length > 0){
                if(data[0].prerequisite[0].prerequisite_id !== null){
                    console.log('prerequisite and not null');
                    $('#prerequisite').append(`<option>${data[0].prerequisite[0].prerequisite.title}</option>`).trigger('change');
                }else if(data[0].prerequisite[0].prerequisite_id === null){
                    console.log('prerequisite and null');
                    $('#prerequisite').append(`<option>No posee Prerequisito</option>`).trigger('change');
                }    
            }else{
                console.log('no prerequisite');
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

            //$('#contenido').val(data[0].contenido);
            
            $('.content-list').html('');
            contentData = [];
            i = 0;
            data[0].content.forEach(element => {
                contentData[i] = element.text;
                $('.content-list').append(`<li value="${i++}" class="list-element list-group-item"><span class="list-text">${element.text}</span></li>`);
            //console.log(i++ +' '+element.text);
            });
            $('.content-list')

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
        
        $("#accion-form :input").prop('readonly', false);
        $("#accion-form select").prop('disabled', false);
        $("#accion-aceptar").removeClass("hidden");
        $("#docs").removeClass("hidden");
        
    $('.course-list').off();
   });
}//DETAILS MODAL / DETALLES DE ACCION DE FORMACION

function editarAccion(url){
    $(".loader").removeClass("hidden");
    //$("#accion-form").addClass("hidden");
    $('.course-code').parent().removeClass('has-error');
    $('.code-error-text').hide();
    $('.read-only-docs').hide();
    $("[name=_method]").val("PUT");
    //$("#accion-form").attr("action", url);
    $("#accion-label").html("Editar Acción de formación");

    $(".fileinput-filename").empty();

    $('.radio-prerequisite').hide();
    $('.select-prerequisite-helper').show();

    $.get(url,function(data,status){
            data=JSON.parse(data);
            console.log(data);
            $('.course-id').val(data[0].id);
            $('.course-code').val(data[0].code);
            console.log(data[0].prerequisite[0]);
            $('#prerequisite').html('');
            
            if(data[0].prerequisite.length > 0){
                if(data[0].prerequisite[0].prerequisite_id !== null){
                    console.log('prerequisite and not null');
                    $('#prerequisite').append(`<option value=${data[0].prerequisite[0].prerequisite_id}>${data[0].prerequisite[0].prerequisite.title}</option>`).trigger('change');
                }else if(data[0].prerequisite[0].prerequisite_id === null){
                    console.log('prerequisite and null');
                    $('#prerequisite').append(`<option value=""}>No posee Prerequisito</option>`).trigger('change');
                }    
            }else{
                console.log('no prerequisite');
                $('#prerequisite').append(`<option value=""}>No posee Prerequisito</option>`).trigger('change');
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
           
            //console.log(data[1]);
            $('.content-list').html('');
            contentData = [];
            i = 0;
            data[0].content.forEach(element => {
                contentData[i] = element.text;
                $('.content-list').append(`<li value="${i++}" class="list-element list-group-item form-inline">
                        <span class="list-text">${element.text}</span>
                        <span class="badge remove-badge"><i class="fa fa-remove"></i></span>
                    </li>`);
               //console.log(i++ +' '+element.text);
            });

            $('.remove-badge').on('click',function(e) { //removes the selected content from the list and array
                contentData.splice($(this).parent().val(),1);
                //console.log(contentData);
                let siblings = $(this).parent().siblings();
                for(let prop in siblings){ //lowers the value of next siblings by 1
                    if(siblings[prop].value > $(this).parent().val()){
                        siblings[prop].value --;
                    }
                }
                $(this).parent().remove();
            });

            /* $('.list-element').on('click',function(e){ //adds a textarea into the list element to edit it's content
                console.log(edit);
                if(e.target !== e.currentTarget) return; //prevents event from triggering when clicking on any of the children
                $(this).children('.list-text').addClass('hidden'); //hides the span element that contains the old text value
                $(this).append(`<textarea style="width:100%" type="text" id="list-textarea-${$(this).val()}" class="form-control" value="${$(this).text()}"/>`); //adds the textarea
                $('#list-'+$(this).val()).focus(); //focuses the cursor on the new textarea
                
                $('#list-textarea-'+$(this).val()).on('keyup',function(event){ //substitues the data in the list and contentData array
                    if((event.key === "Enter") || (event.key === ".")){ 
                        $(this).siblings('.list-text').removeClass('hidden').text(`${$(this).val()}`);
                        contentData[$(this).parent().val()] = $(this).val();
                        $(this).remove(); //removes the textarea element from the dom
                    }
                });
            }); */

            //console.log(data[0].file.length);

            if(data[0].file.length > 0){
                $('.fileinput').addClass('fileinput-exists').removeClass('fileinput-new');
                f = 0;
                data[0].file.forEach(element => {
                    if(element.type_id == 1){
                        $('#l_manual_f').text(element.path);
                    }
                    if(element.type_id == 2){
                        $('#l_manual_p').text(element.path);
                    }
                    if(element.type_id == 3){
                        $('#l_guia').text(element.path);
                    }
                    if(element.type_id == 4){
                        $('#l_presentacion').text(element.path);
                    }
                    
                    f++;
                });
            }else{
                $('.fileinput').addClass('fileinput-new').removeClass('fileinput-exists');
            }

            $(".loader").addClass("hidden");
            $("#accion-form").removeClass("hidden");

        });
    $("#accion-modal").modal();

    
}//UPDATE MODAL / EDITAR ACCION DE FORMACION


$(document).ready(function(){
    console.log('loaded');
    $.ajaxSetup({ //csrf token
        headers:{
            'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
        }
    });//end csrf token

    $('.tab-submit').hide();
    $('.tab-button-back').hide();
    $('.tab-button-next').addClass('disabled');

    //tab buttons
    let tabIndex = 0;
    $('.tab-button-next').on('click',function(){
        tabIndex = tabIndex+1;
        tabSwitch(tabIndex);
        //console.log('next index:'+tabIndex);
    })
    $('.tab-button-back').on('click',function(){
        tabIndex = tabIndex-1;
        tabSwitch(tabIndex);
        //console.log('back index:'+tabIndex);
    })

    //when modal closes
    $(".modal").on("hidden.bs.modal", function () {
        $(".select2").prop("disabled", true);
        $(".select2").each(function () {
            $(this).prop("disabled", false);
        });

        $(".create-course-form :input").prop("disabled", false);
        $(".code-error-text").hide();
        console.log("closed!");
        
        tabIndex = 0;
        $(".tab-button-next").show();
        $(".tab-button-back").hide();
        $(".tab-submit").hide();
        $(".tab-button-close").show();
        $(".tab-button-next").addClass("disabled");
        tabSwitch(0);
    });//end ON modal close event
    
    //when modal opens
    $('.modal').on('shown.bs.modal',function(){
        
        tabCheck('0');
        
        console.log('open!');

        draggableCheck();
        listButtonsCheck();

        $('.select2').each(function(){
            $(this).prop('disabled',false);
        });

        $('.0-tab-input.course-code').trigger('focus');

        //enable inputs when edit modal opens
        if($('#accion-label').html() == 'Editar acción de formación'){
            $('.create-course-form :input').prop('disabled',false);
        }else{
            $('.create-course-form :input').prop('disabled',true);
        }

        //course code validation

        $('.course-code').prop('disabled',false);

        $('.0-tab-input.course-code').on('input',function(){ //code validation ajax event call

            $('.0-tab-input.course-code').focus();
            console.log('code validation');
            let codeValue = $('.course-code').val();
            $.ajax({
                data: {'codeValue' : codeValue},
                type:'POST',
                url: 'codes',
                dataType: 'json',
                success: function(response){
                    //console.log(response);
                    if(response === true){
                        $('.course-code').parent().addClass('has-error');
                        $('.code-error-text').show();
                        console.log("code already exists");
                        $('.create-course-form :input').prop('disabled',true);
                        $('.course-code').prop('disabled',false);
                       // $('.course-code').trigger('focus');
                        
    
                    }else{
                        $('.course-code').parent().removeClass('has-error');
                        $('.code-error-text').hide();
                        console.log("code available");
                        $('.create-course-form :input').prop('disabled',false);
                        $('.select2').prop('disabled',false);
                    }
                    //console.log("success");
                },
                error: function(response){
                
                }
            });
        });//end code validation event ajax call
    });//end ON modal open event

    //content list
    $('.undo-btn').hide();

    let tempElement; //helper to store temporary element to undo deletion

    $('.undo-btn').on('click',function(){ //undo deletion
        console.log(tempElement);
        $('.content-list').append(tempElement);
        eventRefresh();
    });

    function eventRefresh(){ //content list event refresh

        console.log('function loaded');
    
        $('.list-item-edit').off();
        $('.list-item-delete').off();
    
        $('.list-item-edit').on('click',function(){ //edit
            let textElement = $(this).siblings('.list-text');
            textElement.on('keypress',function(e){
                if((e.key === 'Enter') || (e.key === '.')){
                    textElement.blur();
                }
            });

            console.log('edit function');
            $(this).siblings().prop('contenteditable',true);
            let contentEle = $(this).siblings()[0];
            //console.log($(contentEle).children());
            const range = document.createRange();
            const selection = window.getSelection();
            range.setStart(contentEle, contentEle.childNodes.length);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);

        });//edit list item
    
        $('.list-item-delete').on('click',function(){ //delete
          //console.log($(this));
          //console.log($(this).parent('.list-element'));
          $('.undo-btn').show();
          tempElement = $(this).parent('.list-element'); 
          $(this).parent('.list-element').remove();
          return tempElement;
        }); // delete list item
        return tempElement;
    }//end event refresh


    
    $('.add-btn').on('click',function(){// add new element
        let listItemValue = $('.list-element').length;
        $('.content-list').prepend(`<li value="${listItemValue}" class="list-element list-group-item"><span class="list-text"><p>Nuevo contenido</p></span><span class="glyphicon glyphicon-pencil list-item-edit" aria-hidden="true"></span><span class="glyphicon glyphicon-trash list-item-delete" aria-hidden="true"></span></li>`);
        
        //console.log(listItemValue);

        //console.log($(`.list-element[value=${listItemValue}]`).children());
  
        $(`.list-element[value=${listItemValue}] span:first-child`).prop('contenteditable',true);
  
        let contentEle = $(`.list-element[value=${listItemValue}] span:first-child`)[0];
        let tempElement = $(`.list-element[value=${listItemValue}] .list-text`);
        const range = document.createRange();
        const selection = window.getSelection();
        range.setStart(contentEle, contentEle.children.length);
        range.collapse(true);
        selection.removeAllRanges();
        selection.addRange(range);
        
        //need event lose focus on 'enter' or 'dot' keypress

        //console.log($(`.list-element[value=${listItemValue}] .list-text`));
        tempElement.on('keypress',function(e){
            if((e.key === 'Enter') || (e.key === '.')){
                tempElement.blur();
            }
        }); //end lose focus event

        eventRefresh();
  
      })//end add new element to list event
      
    //enable navigation if inputs are not empty
    tabCheck('0'); 
});