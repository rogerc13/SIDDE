/* function setCourse(){
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
                    console.log(formData);
                    $.ajax({       
                        data:formData,
                        type:"POST",
                        url: newUrl,
                        dataType: "json",
                        contentType: false,
                        processData: false,
                        success: function (response){
                            console.log(response);
                            success = true;
                            method = '';
                            window.location = "acciones_formacion/onSubmitAlert/"+success;
                        },
                        error: function (response){
                            success = false;
                            console.log(response);
                            method = '';
                            window.location = "acciones_formacion/onSubmitAlert/"+success;
                        }
                    });
            } */

/* $(document).ready(function (){
    $.ajaxSetup({
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
        }); */
/*     contentData = [];

    const content = document.querySelector('.course-content');
    const contentList = document.querySelector('.content-list');
    let counter = 0;
    content.addEventListener("keyup",(event) =>{
        if((event.key === "Enter") || (event.key === ".")){
            
            contentData.push(content.value); //inserts content into array

            let listElement = document.createElement('li');

            listElement.value = counter;
            listElement.classList.add("list-element");
            listElement.classList.add("list-group-item");
            listElement.appendChild(document.createTextNode(content.value));
            //console.log("contentData length: "+contentData.length);
            $(listElement).append(
                '<span class="badge remove-badge"><i class="fa fa-remove"></span></i>'
            );
            $(listElement).val(contentData.length - 1); //sets list value according to contentData array length
            
            
            contentList.appendChild(listElement);
            
            $(".remove-badge").off('click').on('click',function () {  //removes selected item from content list and content list data array
            
                contentData.splice($(this).parent().val(), 1);
                
                let siblings = $(this).parent().siblings();

                for (let prop in siblings) {
                    //lowers the value of next siblings by 1
                    if (siblings[prop].value > $(this).parent().val()) {
                        siblings[prop].value--;
                    }
                }

                $(this).parent().remove();
                
            });

            content.value = "";
            counter++;
            
            console.log(contentData);
            return contentData; 
        }  
    }); */
$(document).ready(function () {
    
    // Allow external initialization (for update view)
    window.setInitialContentData = function(arr) {
        contentData = Array.isArray(arr) ? arr.slice() : [];
        editIndex = null;
        renderContentList();
    };

    let contentData = window.initialContentData ? window.initialContentData.slice() : [];
    let editIndex = null;

    function renderContentList() {
        const $list = $(".content-list");
        $list.empty();
        contentData.forEach((item, idx) => {
            $list.append(`
                <li class="list-group-item d-flex align-items-center" style="display: flex; justify-content: space-between;" data-index="${idx}">
                    <span class="content-text">${item}</span>
                    <span style="margin-left: auto; display: flex; gap: 0.5rem; align-items: center;">
                        <span class="drag-handle" style="cursor: move; margin-right: 0.5rem;">
                            <i class="glyphicon glyphicon-menu-hamburger"></i>
                        </span>
                        <button class="btn btn-xs btn-primary edit-content-btn" type="button"><i class="glyphicon glyphicon-pencil"></i></button>
                        <button class="btn btn-xs btn-danger remove-content-btn" type="button"><i class="glyphicon glyphicon-remove"></i></button>
                    </span>
                </li>
            `);
        });

        // Re-enable sortable after rendering
        if ($list.hasClass('ui-sortable')) {
            $list.sortable('destroy');
        }
        $list.sortable({
            handle: '.drag-handle',
           update: function () {
                // Rebuild contentData from current DOM order
                const newOrder = [];
                $list.children('li').each(function () {
                     newOrder.push($(this).find('.content-text').text().trim());
                });
                contentData = newOrder;
                // Keep data-index attributes in sync without re-rendering
                $list.children('li').each(function (idx) {
                    $(this).attr('data-index', idx);
                });
            }
        });
    }

    // Add content
    $(document).on('click', '#add-content-btn', function () {
        const value = $('#content-input').val().trim();
        if (value) {
            if (editIndex !== null) {
                contentData[editIndex] = value;
                editIndex = null;
                $('#add-content-btn').html('<span class="glyphicon glyphicon-plus"></span> Añadir').removeClass('btn-warning').addClass('btn-success');
            } else {
                contentData.push(value);
            }
            $('#content-input').val('');
            renderContentList();
        }
    });

    // Edit content
    $(document).on('click', '.edit-content-btn', function () {
        const idx = $(this).closest('li').data('index');
        $('#content-input').val(contentData[idx]).focus();
        editIndex = idx;
        $('#add-content-btn').html('<span class="glyphicon glyphicon-floppy-disk"></span> Guardar').removeClass('btn-success').addClass('btn-warning');
    });

    // Remove content
    $(document).on('click', '.remove-content-btn', function () {
        const idx = $(this).closest('li').data('index');
        contentData.splice(idx, 1);
        if (editIndex === idx) {
            $('#content-input').val('');
            editIndex = null;
            $('#add-content-btn').html('<span class="glyphicon glyphicon-plus"></span> Añadir').removeClass('btn-warning').addClass('btn-success');
        }
        renderContentList();
    });

    // Cancel edit on input blur (optional, or add a cancel button if desired)
    $('#content-input').on('keydown', function (e) {
        if (e.key === 'Escape') {
            $(this).val('');
            editIndex = null;
            $('#add-content-btn').html('<span class="glyphicon glyphicon-plus"></span> Añadir').removeClass('btn-warning').addClass('btn-success');
        }
    });

    // Reset on modal close
    $("#accion-modal").on("hidden.bs.modal", function () {
        $(".content-list").children().remove();
        $('#content-input').val('');
        editIndex = null;
        contentData = [];
        $('#add-content-btn').html('<span class="glyphicon glyphicon-plus"></span> Añadir').removeClass('btn-warning').addClass('btn-success');
    });

    // Expose contentData for form submission if needed
    window.getContentData = function () { return contentData; };
});