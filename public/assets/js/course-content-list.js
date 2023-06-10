$(document).ready(function (){


    let contentData = [];

    const content = document.querySelector('.course-content');
    const contentList = document.querySelector('.content-list');
    let counter = 0;
    console.log("element: "+content);
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

            //console.log(contentData);   
        }  
    });
});