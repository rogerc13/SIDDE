$(document).ready(function(){
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });
    $('.select-prerequisite-helper').hide();
    console.log("active");
    $(".radio-prerequisite").on('click',function(e){
        $('.radio-prerequisite').not(this).removeClass('checked');
        $(this).addClass('checked');
        if($(this).hasClass('no-prerequisite')){
            console.log('no-prerequisite');
        }else{
            console.log('has-prerequiste');
            $.ajax({
                type: "POST",
                url: "prerequisite",
                dataType: 'JSON',
                success: function(response){
                    $(".radio-prerequisite").hide();
                    $(".select-prerequisite-helper").show();
                    $("#prerequisite").html('');
                    response.courses.forEach(element => {
                        $('#prerequisite').append(`<option value="${element.code}">
                                                            ${element.code} |
                                                            ${element.title}
                                                            </option>`);
                    });
                    $("#prerequisite").append(
                        `<option value="">No Posee Prerequisito</option>`
                    );
                    console.log(response.courses);
                },
                error: function(response){
                    console.log(response);
                }
            });
        }  
        //if option NO is selected
            //set new course prerequisite value to NULL
        //if option YES is selected
            //get list of courses from database
            //hide radio buttons
            //draw select input with list of courses
                //need id of course and name
                //need to add Ninguno option to list with value of NULL
    });


    //check if update modal is selected
    //empty and draw new prerequisite list

    //on modal open/close remove select course list if exists and show radio buttons
    $('.modal').on('hidden.bs.modal',function(){
        $(".select-prerequisite-helper").hide();
        $('.radio-prerequisite').show();
        $('.no-prerequisite').addClass('checked');
        $('.no-prerequisite').siblings().removeClass('checked');
    })
    
})