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
$(document).ready(function (){
    
    /* $.ajaxSetup({
        headers:{
            'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
        }
    }); */

    $("#accion-modal").on("hidden.bs.modal", function(){
        $(".content-list").children().remove();// deletes list items to avoid incorrect list values when modal re opens
        counter = 0; //sets global list value to 0
        contentData = [];
    });    
});