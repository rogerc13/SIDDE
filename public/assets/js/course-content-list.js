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
    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getWrapperFromElement($el) {
        const $wrapper = $el.closest('.form-group');
        return $wrapper.length ? $wrapper : $(document.body);
    }

    function getState($wrapper) {
        let state = $wrapper.data('courseContentState');
        if (!state) {
            state = { contentData: [], editIndex: null };
            $wrapper.data('courseContentState', state);
        }
        return state;
    }

    function setAddButtonMode($wrapper, mode) {
        const $button = $wrapper.find('.add-content-btn').first();
        if (!$button.length) {
            return;
        }

        if (mode === 'edit') {
            $button
                .html('<span class="glyphicon glyphicon-floppy-disk"></span> Guardar')
                .removeClass('btn-success')
                .addClass('btn-warning');
        } else {
            $button
                .html('<span class="glyphicon glyphicon-plus"></span> Añadir')
                .removeClass('btn-warning')
                .addClass('btn-success');
        }
    }

    function renderContentList($wrapper) {
        const state = getState($wrapper);
        const $list = $wrapper.find('.content-list').first();
        if (!$list.length) {
            return;
        }

        $list.empty();
        state.contentData.forEach((item, idx) => {
            $list.append(`
                <li class="list-group-item d-flex align-items-center" style="display: flex; justify-content: space-between;" data-index="${idx}">
                    <span class="content-text">${escapeHtml(item)}</span>
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
                state.contentData = newOrder;

                // Keep data-index attributes in sync without relying on jQuery .data() cache
                $list.children('li').each(function (idx) {
                    $(this).attr('data-index', idx);
                });
            }
        });
    }

    // Allow external initialization (for update view)
    window.setInitialContentData = function (arr) {
        const normalized = Array.isArray(arr) ? arr.slice() : [];

        // Prefer the content list inside the main course modal when it exists.
        // This prevents rendering into the standalone update partial (also included on the index page).
        const $modalList = $('#accion-modal').find('.content-list').first();
        const $targetList = $modalList.length
            ? $modalList
            : $(".content-list").filter(function () { return $(this).closest('#accion-modal').length === 0; }).first();

        const $wrapper = $targetList.length ? $targetList.closest('.form-group') : $(".content-list").first().closest('.form-group');
        if (!$wrapper.length) {
            return;
        }

        const state = getState($wrapper);
        state.contentData = normalized;
        state.editIndex = null;
        setAddButtonMode($wrapper, 'add');
        renderContentList($wrapper);
    };

    // Add content
    $(document).on('click', '.add-content-btn', function () {
        const $wrapper = getWrapperFromElement($(this));
        const state = getState($wrapper);
        const $input = $wrapper.find('.content-input').first();
        const value = $input.val().trim();

        if (!value) {
            return;
        }

        if (state.editIndex !== null) {
            state.contentData[state.editIndex] = value;
            state.editIndex = null;
            setAddButtonMode($wrapper, 'add');
        } else {
            state.contentData.push(value);
        }

        $input.val('');
        renderContentList($wrapper);
    });

    // Edit content
    $(document).on('click', '.edit-content-btn', function () {
        const $wrapper = getWrapperFromElement($(this));
        const state = getState($wrapper);
        const idx = Number($(this).closest('li').attr('data-index'));

        if (!Number.isFinite(idx) || idx < 0 || idx >= state.contentData.length) {
            return;
        }

        $wrapper.find('.content-input').first().val(state.contentData[idx]).focus();
        state.editIndex = idx;
        setAddButtonMode($wrapper, 'edit');
    });

    // Remove content
    $(document).on('click', '.remove-content-btn', function () {
        const $wrapper = getWrapperFromElement($(this));
        const state = getState($wrapper);
        const idx = Number($(this).closest('li').attr('data-index'));

        if (!Number.isFinite(idx) || idx < 0 || idx >= state.contentData.length) {
            return;
        }

        state.contentData.splice(idx, 1);

        if (state.editIndex === idx) {
            $wrapper.find('.content-input').first().val('');
            state.editIndex = null;
            setAddButtonMode($wrapper, 'add');
        } else if (state.editIndex !== null && state.editIndex > idx) {
            // Keep editIndex consistent after deleting an item before it
            state.editIndex -= 1;
        }

        renderContentList($wrapper);
    });

    // Cancel edit on input blur (optional, or add a cancel button if desired)
    $(document).on('keydown', '.content-input', function (e) {
        if (e.key === 'Enter') {
            // Avoid submitting the surrounding form when adding/editing content items.
            e.preventDefault();
            e.stopPropagation();

            const $wrapper = getWrapperFromElement($(this));
            $wrapper.find('.add-content-btn').first().trigger('click');
            return;
        }

        if (e.key === 'Escape') {
            const $wrapper = getWrapperFromElement($(this));
            const state = getState($wrapper);
            $(this).val('');
            state.editIndex = null;
            setAddButtonMode($wrapper, 'add');
        }
    });

    // Reset on modal close
    $("#accion-modal").on("hidden.bs.modal", function () {
        const $modal = $(this);
        const $wrapper = $modal.find('.content-list').first().closest('.form-group');
        if ($wrapper.length) {
            const state = getState($wrapper);
            state.contentData = [];
            state.editIndex = null;
            $wrapper.find('.content-list').first().children().remove();
            $wrapper.find('.content-input').first().val('');
            setAddButtonMode($wrapper, 'add');
        }
    });

    // Expose contentData for form submission if needed
    window.getContentData = function () {
        // Prefer modal (create) state if open/exists, otherwise use first content list on page.
        const $modalWrapper = $('#accion-modal').find('.content-list').first().closest('.form-group');
        if ($modalWrapper.length) {
            return getState($modalWrapper).contentData;
        }

        const $wrapper = $('.content-list').first().closest('.form-group');
        return $wrapper.length ? getState($wrapper).contentData : [];
    };
});