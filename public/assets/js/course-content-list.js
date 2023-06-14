function setCourse(){
                    console.log($('meta[name="csrf-token"]').attr('content'));
                    let formData = $('#accion-form').serializeArray();
                    formData.push({name:'contentData',value:contentData});
                    console.log(typeof formData);
                    console.log(formData);
                    $.ajax({       
                        type:"POST",
                        url: "acciones_formacion/",
                        data:{'formData':formData},
                        //contentType: "application/json",
                        dataType: "json",
                        success: function (response){
                            console.log(response);
                            //window.location.reload();
                        },
                        error: function (response){
                            console.log(response);
                        }
                    });
                
            }

$(document).ready(function (){
    

    $.ajaxSetup({
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
        });

    


    contentData = [];

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
            
            
            listElement.addEventListener('click',(e) =>{ //removes the selected content from the list and array
                contentData.splice(e.target.value,1);

                let siblings = $(e.target).siblings();
                
                for(let prop in siblings){ //lowers the value of next siblings by 1
                    if(siblings[prop].value > e.target.value){
                        siblings[prop].value --;
                    }
                }
                e.target.remove();
            },false);
            
            contentList.appendChild(listElement);
            
            content.value = "";
            counter++;

            return contentData; 
        }  
    });


    $("#accion-modal").on("hidden.bs.modal", function(){
        $(".content-list").children().remove();// deletes list items to avoid incorrect list values when modal re opens
        counter = 0; //sets global list value to 0
    });
});